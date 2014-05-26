# Task 1
Write a function/method which exactly duplicates the functionality of [`parse_url()`](http://php.net/parse_url). Do so without without using regular expressions.

## More Info

I realised that to *exactly* duplicte the functionality of an existing PHP function the easiest way would be to do a straight port of the C function into PHP and then use PHP's own unit tests to verify that it's functionally correct.

My implementation replicates error conditions and all edge cases *exactly*, the only tests that fail are due to limitations in PHP and *not* due to the implementation. Namely I can't exactly replicate the messages & codes of the warnings and I can't make it look like warnings were triggered in the stack frame above the current function.

## Running Tests

```
make test
```