<?php
use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;


class IpAddress extends TestCase
{
    public function testIpAddress()
    {
        $data = new stdClass();
        $data->ip = "192.168.0.1";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("ip")->required->ipAddress;

        $this->assertTrue($data_verifier->verify());
    }

    public function testInvalidIpAddress()
    {
        $data = new stdClass();
        $data->ip = "999.999.999.999";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("ip")->required->ipAddress;

        $this->assertFalse($data_verifier->verify());
    }
}