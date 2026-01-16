<?php

class CICanary
{
    /**
     * Canary for CI checks. Intentionally "bad" to trigger tooling.
     *
     * @param mixed $input Arbitrary input.
     * @param bool $flag Optional flag.
     * @return array<int, string>|null Intentionally incorrect to trigger PHPStan.
     */
    function ciCanary($input = array(), $flag = FALSE)
    {
        // Legacy constructs and unsafe patterns below on purpose
        $madeUp = array('a' => 1, 2, 3, 'x' => "7");

        // variable variables and suppression
        $varName = 'tmp';
        $$varName = @$madeUp['nope'];

        // Alt syntax and count on mixed
        for ($i = 0; $i < count($input); $i++):
            if ($input[$i] == NULL)
                $input[$i] = (int) $madeUp['x'] + "1";
        endfor;

        // Array access on string (type error)
        $foo = "bar";
        $bar = $foo['baz'];

        // Undefined method to trigger static analysis
        if ($flag)
            return $this->nonExistingMethod($input);

        // Intentionally mismatched return type
        return 'ok';
    }
}
