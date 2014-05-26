<?php
/*
 * There are a few approaches to writing a dependency injection container, the
 * variances between them and some of the pros/cons are something like:
 *
 *  1. Do we expect classes to have to implement an interface to be compatible
 *     with dependency injection?
 *     e.g. 'implements Needs_PDO { function set_PDO(PDO ...'
 *
 *  2. Do we take complete ownership of creating new class instances?
 *     e.g. $instance = DI::create('ClassName', 1, 2, 3);
 *
 *  3. Do we fill dependencies after the class has been created?
 *     e.g. DI::inject($instance);
 *
 *  4. Do we make classes extend a base class and do all the logic in
 *     the constructor? e.g. 'extends Component'
 *
 *  5. Do we use reflection to work out which classes to inject into the
 *     constructor?
 *
 *  6. How do we register providers for types? Are we doing dumb class name
 *     matching, or doing checks with 'instanceof'?
 *
 *  7. Do we use 'set_PDO' methods?
 *
 *  8. Do we inject dependencies into variables?
 *
 *
 *  Concerns:
 *
 *  1. and 4. This means each class is bound to our specific implementation of
 *     the dependency injection framework, which kinda defeats the point.
 * 
 *  3. is absolutely needed because there has to be some way to tell the DI
 *     framework to do it's magic.
 *
 *  5. the last time I used reflection it was quite slow, but compared to what?
 *     I wouldn't want to rule-out
 *
 *  2. would be a nice to have, but requires reflection to determine the types
 *     required by the constructor.
 *
 *  6. Checking for types with 'instanceof' would mean doing a search through
 *     all providers rather than a dictionary lookup.
 *
 *  7. What about all the boilerplate introduced by the 'set_XXX' methods?
 *
 *  8. Might this conflict with existing classes?
 *
 * 
 * General Thoughts:
 *
 * While I think using 'Needs_PDO' interfaces which specify the setter are cool
 * and with a little bit of reflection magic it can be linked upto provides
 * automagically it would mean altering every class used with the system and
 * significantly increases the amount of boilerplate.
 *
 * So basically we're down to 'set_XXXX' methods and a dictionary lookup of
 * providers which avoids reflection and should be faster...
 */

class DI_Exception extends Exception {}

/**
 * @author php-dependency-injector@midnight-labs.org
 * @copyright Copyleft, no rights reserved.
 *
 * The tiny dependency injection framework allows dependencies to be registered
 * and provided to classes on demand, this makes decoupling functionality and
 * configuration easier.
 *
 * For more info, see: http://en.wikipedia.org/wiki/Dependency_injection
 *
 * It supports two styles of dependency injection: variable binding and setters.
 * No 'smart' matching happens, so dependency names must match exactly.
 * It does not support inferring dependencies based on interfaces or inheritence.
 * It does not do any introspection or reflection upon the class, this is for speed. 
 *
 * Sample usage:
 *
 *    class Controller {
 *      public $db;
 *      private $_user;
 *
 *      function set_user($user) {
 *        $this->_user = $user;
 *      }
 *
 *      function deleteme () {
 *        if( $this->_user != NULL ) {
 *          assert( $this->db != NULL );
 *          $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
 *          $stmt->execute( [$this->_user->id] );
 *        }
 *      }
 *    }
 *
 *    DI::register('db', function(){ return new PDO(...); });
 *    DI::register('user', function(){ return User::fetch($_SESSION['user_id']); });
 *
 *    $controller = DI::inject(new Controller());
 *    $controller->deleteme();
 */
class DI {
	private static $_providers = array();

    /**
     * @return array of dependencies which can be provided
     */
	static function providers () {
		return array_keys(self::$_providers);
	}


	/**
	 * Utility method which calling `register_class` or `register_callback`
	 * depending on the type of the arguments given.
	 *
	 *   DI::register(new DatabaseProvider());
	 *   DI::register('db', function () {..});
	 *   DI::register(['db', 'pdo'], function () {..});
	 *
	 * @param mixed $what_or_provider Object, List or String
	 * @param calable $callable Optional callback
	 * @throws DI_Exception on error
	 */
	static function register ($what_or_provider, $callable=NULL) {
		if( is_object($what_or_provider) ) {
			assert( $callable === NULL );
			return self::register_class($what_or_provider);
		}
		if( is_string($what_or_provider) || is_array($what_or_provider) ) {
			assert( $callable !== NULL );
			if( ! is_array($what_or_provider) ) {
				$what_or_provider = array($what_or_provider);
			}
			return self::register_callback($what_or_provider, $callable);
		}
	}


	/**
	 * Register a class which has 'provide_XXX' methods which return
	 * the requested dependency for XXX.
	 *
	 * This is roughly equivalent to calling:
	 *
	 *   DI::register_callback('XXX', array($obj, 'provide_XXX'));
	 *
	 * @param object $provider Provider class	 
	 * @throws DI_Exception on duplicate provider
	 */
	static function register_class ($provider) {
		assert( is_object($provider) );
		foreach( get_class_methods($provider) AS $method ) {
			$method = strtolower($method);
			$prefix = 'provide_';
			if( substr($method, 0, strlen($prefix)) != $prefix ) {
				continue;
			}
			$what = substr($method, strlen($prefix));
			self::register_callback([$what], array($provider, $method));
		}
	}


	/**
	 * Register a callback to provide one or more dependencies.
	 *
	 * @param array $what_list array of dependencies the callback can provide
	 * @param callable $callable Callback to provide them
	 * @throws DI_Exception on duplicate provider
	 */
	static function register_callback (array $what_list, $callable) {		
		assert( is_callable($callable) );
		foreach( $what_list as $what ) {			
			if( isset(self::$_providers[$what]) ) {
				throw new DI_Exception(sprintf("Duplicate provider for '%s'", $what));
			}
			assert( is_string($what) );
			self::$_providers[$what] = $callable;
		}
	}


	/**
	 * Provide the caller with a named dependency.
	 *
	 * @param string $what Name of the dependency required
	 * @param object $for_obj [optional] Object which requires the dependency
	 * @throws DI_Exception if the requirement is unknown
	 */
	static function give ($what, $for_obj = NULL) {
		assert( is_string($what) );
		if( $for_obj !== NULL ) {
			assert( is_object($for_obj) );
		}
		$what = strtolower($what);
		if( FALSE == isset(self::$_providers[$what]) ) {
			throw new DI_Exception(sprintf("Unknown requirement '%s'", $what));
		}
		return call_user_func(self::$_providers[$what], $what, $for_obj);
	}


	/**
	 * Inject dependencies into an object using it's methods and values.
	 *
	 * All unset public variables which have the same name as a dependency
	 * which can be provided will be assigned the dependency.
	 *
	 * All public functions named 'set_XXX' where 'XXX' is a dependency
	 * will be called with the dependency as the first argument.
	 * 
	 * @param object $obj A class instance to be injected
	 * @return The same object you provided
	 */
	static function inject ($obj) {
		assert( is_object($obj) );

		// Call the setter methods, supports both `set_var` and `setVar` style
		foreach( get_class_methods($obj) AS $method ) {
			$method = strtolower($method);
			if( strlen($method) > 4 && substr($method, 0, 3) != 'set' ) {
				continue;
			}
			$need = ltrim(substr($method, 3), '_');
			if( isset(self::$_providers[$need]) ) {
				$provider = self::$_providers[$need];
				$result = call_user_func($provider, $need, $obj);
				call_user_func(array($obj, $method), self::give($need, $obj));
			}
		}

		// Inject into variables
		foreach( get_object_vars($obj) AS $var => $value ) {
			if( $value === NULL ) {				
				$need = strtolower($var);
				if( FALSE == isset(self::$_providers[$need]) ) {
					continue;
				}
				$obj->{$var} = self::give($need, $obj);
			}
		}
		return $obj;
	}
}
