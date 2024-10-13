<?php

namespace Gravity;

/**
 * Class DataVerify
 *
 * This class allows for validating data based on various tests.
 *
 * @property DataVerify $required                   Checks if the field is required
 * @property DataVerify $string                     Checks if the value is a string
 * @property DataVerify $numeric                    Checks if the value is numeric
 * @property DataVerify $int                        Checks if the value is an integer
 * @property DataVerify $date                       Checks if the value is a valid date (default format YYYY-mm-dd)
 * @property DataVerify $email                      Checks if the value is a valid email
 * @property DataVerify $disposable_email           Checks if the email is not a disposable address
 * @property DataVerify $ip_address                 Checks if the value is a valid IP address IPV4 & IPV6
 * @property DataVerify $street_address             Checks if the value is a valid street address
 * @property DataVerify $contains_upper             Checks if the value contains at least one uppercase letter
 * @property DataVerify $contains_lower             Checks if the value contains at least one lowercase letter
 * @property DataVerify $contains_number            Checks if the value contains at least one digit
 * @property DataVerify $alphanumeric               Checks if the value is alphanumeric
 * @property DataVerify $not_alphanumeric           Checks if the value is not alphanumeric
 * @property DataVerify $contains_special_character Checks if the value contains a special character
 *
 * @method DataVerify date(string $format)                  Checks if the value is a valid date (default format Y-m-d), you can specify other format in params
 * @method DataVerify greater_than(int|float $value)        Checks if the value is greater than a limit, works with dates (format YYYY-mm-dd), numerics and int values
 * @method DataVerify lower_than(int|float $value)          Checks if the value is less than a limit, works with dates (format YYYY-mm-dd), numerics and int values
 * @method DataVerify between(int|\DateTime|string|float $min, int|\DateTime|string|float $max)     Checks if the value is between two limits, works with dates (format YYYY-mm-dd or YYYY-mm or YYYY), numerics and int values
 * @method DataVerify min_length(string|int $length)         Checks if the length of the value is greater than or equal to $length
 * @method DataVerify max_length(string|int $length)         Checks if the length of the value is less than or equal to $length
 */

class DataVerify
{
    public array|object $data;
    private array $errors = [];
    private object $error_message;
    private array $fields = [];
    private bool $valid = true;
    private object $alias;
    const DISPOSABLE = ["@yopmail", "@ymail", "@jetable", "@trashmail", "@jvlicenses", "@temp-mail", "@emailnax", "@datakop"];

    public function __construct($data) {
        $this->data = $data;
        $this->alias = new \stdClass();
        $this->error_message = new \stdClass();
    }

    public function verify(): bool
    {
        foreach ($this->fields as $field_name => $tests) {
            $value = $this->data->$field_name ?? null;

            foreach ($tests as $test_name => $test_value) {
                if (method_exists($this, $test_name)) {
                    if ($test_name === '_required' || !empty($value)) {
                        if (is_array($test_value) && !empty($test_value)) {
                            $this->valid = $this->$test_name($value, ...$test_value);
                        } else {
                            $this->valid = $this->$test_name($value);
                        }
                    }

                    if (!$this->valid) {
                        // Remove previously added underscore
                        $unprivate_test_name = substr($test_name, 1);
                        $wanted_field_name = $this->alias->$field_name ?? $field_name;
                        $message = $this->error_message->$field_name ?? "The field '$wanted_field_name' failed the test '$unprivate_test_name'";
                        $this->set_error($message, $value, $field_name, $unprivate_test_name);
                    }
                }
            }
        }

        return empty($this->errors);
    }

    public function __get($method): self {
        $method = "_".strtolower($method);
        if (method_exists($this, $method)) {
            $this->fields[array_key_last($this->fields)][$method] = null;
        }
        return $this;
    }

    public function __call($method_name, $arguments): self {
        $method = "_".strtolower($method_name);
        if (method_exists($this, $method)) {
            $this->fields[array_key_last($this->fields)][$method] = $arguments;
        }
        return $this;
    }

    public function field(string $name): self {
        $this->fields[$name] = [];
        return $this;
    }

    public function alias($name): self{
        $field_name = array_key_last($this->fields);
        $this->alias->$field_name = $name;
        return $this;
    }

    public function error_message(string $message): self {
        $field_name = array_key_last($this->fields);
        $this->error_message->$field_name = $message;
        return $this;
    }

    public function get_errors(): array|bool
    {
        return $this->errors;
    }

    private function set_error($message, $value, $data_name, $test_name=null): void
    {
        $this->errors[] = [
            "message" => $message,
            "test" => $test_name,
            "data_name" => $data_name,
            "data_alias" => $this->alias->$data_name ?? null,
            "data" => !empty($value) ? $value : "EMPTY"
        ];
    }

    private function _required($value): bool
    {
        return !empty($value);
    }

    private function _string($value): bool
    {
        return is_string($value);
    }

    private function _between(int|float|\DateTime|string $value, int|float|\DateTime|string $min, int|float|\DateTime|string $max): bool
    {
        if (is_string($value)) {
            if (is_numeric($value)) {
                $value = $value + 0;
            }
        }

        return $value >= $min && $value <= $max;
    }

    private function _disposable_email($value): bool
    {
        $domain = explode(".", strstr($value, "@"));
        return $domain && !in_array($domain[0], self::DISPOSABLE);
    }

    private function _street_address($value): bool
    {
        return @preg_match("/^[a-zA-Z0-9 'éèùëêûîìàòÀÈÉÌÒÙâôöüïäÏÖÜÄËÂÊÎÔÛ-]+$/", $value);
    }

    private function _date($value, $format="Y-m-d"): bool
    {
        return (bool)\DateTime::createFromFormat($format, $value);
    }

    private function _numeric($value): bool
    {
        return is_numeric($value);
    }

    private function _int(string|int $value): bool
    {
        return is_int($value) || (is_string($value) && filter_var($value, FILTER_VALIDATE_INT));
    }

    private function _email($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    private function _ip_address($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP, [FILTER_FLAG_IPV4, FILTER_FLAG_IPV6]);
    }

    private function _min_length($value, $length_greater): bool
    {
        return strlen($value) > $length_greater;
    }

    private function _max_length($value, $length_lower): bool
    {
        return strlen($value) < $length_lower;
    }

    private function _greater_than($value, $greater_than): bool
    {
        return $value > $greater_than;
    }

    private function _lower_than($value, $lower_than): bool
    {
        return $value < $lower_than;
    }

    private function _contains_upper($value): bool
    {
        foreach(str_split($value) as $char)
        {
            if(ctype_upper($char) && !is_numeric($char)) return true;
        }
        return false;
    }

    private function _contains_lower($value): bool
    {
        foreach(str_split($value) as $char)
        {
            if(ctype_lower($char) && !is_numeric($char)) return true;
        }
        return false;
    }

    private function _contains_number($value): bool
    {
        foreach(str_split($value) as $char)
        {
            if(is_numeric($char)) return true;
        }
        return false;
    }

    private function _alphanumeric($value): bool
    {
        return ctype_alnum($value);
    }

    private function _not_alphanumeric($value): bool
    {
        //Regex Match first alphanumeric char
        return !ctype_alnum($value) && !@preg_match("/[\p{L}\p{N}\p{M}]/", $value);
    }

    private function _contains_special_character($value): bool
    {
        return @preg_match('/[^\w\s]/', $value) === 1;
    }
}