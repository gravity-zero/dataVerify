<?php

use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;

class InAndNotInArrayOrObject extends TestCase
{
    public function testInAcceptsValidValues()
    {
        $data = new stdClass();
        $data->status = "active";
        $data->role = "admin";

        $verifier = new DataVerify($data);
        $verifier
            ->field('status')->in(['pending', 'active', 'closed'])
            ->field('role')->in(['user', 'admin', 'moderator']);

        $this->assertTrue($verifier->verify());
    }

    public function testInRejectsInvalidValues()
    {
        $data = new stdClass();
        $data->status = "deleted";

        $verifier = new DataVerify($data);
        $verifier->field('status')->in(['pending', 'active', 'closed']);

        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals('in', $errors[0]['test']);
    }

    public function testInUsesStrictComparison()
    {
        $data = new stdClass();
        $data->value = "1";

        $verifier = new DataVerify($data);
        $verifier->field('value')->in([1, 2, 3]); // Integer array

        $this->assertFalse($verifier->verify()); // "1" !== 1
    }

    public function testInAcceptsIntegerValues()
    {
        $data = new stdClass();
        $data->value = 2;

        $verifier = new DataVerify($data);
        $verifier->field('value')->in([1, 2, 3]);

        $this->assertTrue($verifier->verify());
    }

    public function testNotInAcceptsAllowedValues()
    {
        $data = new stdClass();
        $data->username = "john_doe";

        $verifier = new DataVerify($data);
        $verifier->field('username')->notIn(['admin', 'root', 'system']);

        $this->assertTrue($verifier->verify());
    }

    public function testNotInRejectsForbiddenValues()
    {
        $data = new stdClass();
        $data->username = "admin";

        $verifier = new DataVerify($data);
        $verifier->field('username')->notIn(['admin', 'root', 'system']);

        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals('notIn', $errors[0]['test']);
    }

    public function testNotInUsesStrictComparison()
    {
        $data = new stdClass();
        $data->value = "1";

        $verifier = new DataVerify($data);
        $verifier->field('value')->notIn([1, 2, 3]); // Integer array

        $this->assertTrue($verifier->verify()); // "1" !== 1, accepted
    }

    public function testCombiningInAndNotIn()
    {
        $data = new stdClass();
        $data->role = "moderator";

        $verifier = new DataVerify($data);
        $verifier
            ->field('role')->in(['user', 'moderator', 'admin'])
            ->field('role')->notIn(['superadmin', 'root']);

        $this->assertTrue($verifier->verify());
    }

    public function testInWithEmptyArrayAlwaysFails()
    {
        $data = new stdClass();
        $data->value = "anything";

        $verifier = new DataVerify($data);
        $verifier->field('value')->in([]);

        $this->assertFalse($verifier->verify());
    }

    public function testNotInWithEmptyArrayAlwaysPasses()
    {
        $data = new stdClass();
        $data->value = "anything";

        $verifier = new DataVerify($data);
        $verifier->field('value')->notIn([]);

        $this->assertTrue($verifier->verify());
    }

    public function testInWithMixedTypes()
    {
        $data = new stdClass();
        $data->value1 = true;
        $data->value2 = "string";
        $data->value3 = 42;

        $verifier = new DataVerify($data);
        $verifier
            ->field('value1')->in([true, false])
            ->field('value2')->in(['string', 'other'])
            ->field('value3')->in([42, 100, 200]);

        $this->assertTrue($verifier->verify());
    }

    public function testInWithObject()
    {
        $allowedRoles = new stdClass();
        $allowedRoles->user = true;
        $allowedRoles->admin = true;
        $allowedRoles->moderator = true;

        $data = new stdClass();
        $data->role = "admin";

        $verifier = new DataVerify($data);
        $verifier->field('role')->in($allowedRoles);

        $this->assertTrue($verifier->verify());
    }

    public function testInWithObjectRejectsInvalidValue()
    {
        $allowedRoles = new stdClass();
        $allowedRoles->user = true;
        $allowedRoles->admin = true;

        $data = new stdClass();
        $data->role = "superadmin";

        $verifier = new DataVerify($data);
        $verifier->field('role')->in($allowedRoles);

        $this->assertFalse($verifier->verify());
    }

    public function testNotInWithObject()
    {
        $forbiddenRoles = new stdClass();
        $forbiddenRoles->root = true;
        $forbiddenRoles->superadmin = true;

        $data = new stdClass();
        $data->role = "user";

        $verifier = new DataVerify($data);
        $verifier->field('role')->notIn($forbiddenRoles);

        $this->assertTrue($verifier->verify());
    }

    public function testNotInWithObjectRejectsForbiddenValue()
    {
        $forbiddenRoles = new stdClass();
        $forbiddenRoles->root = true;
        $forbiddenRoles->superadmin = true;

        $data = new stdClass();
        $data->role = "root";

        $verifier = new DataVerify($data);
        $verifier->field('role')->notIn($forbiddenRoles);

        $this->assertFalse($verifier->verify());
    }
}