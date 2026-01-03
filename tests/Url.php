<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;


class Url extends TestCase
{
    public function testDefaultHttpsUrl(): void
    {
        $data = (object)['website' => 'https://example.com'];
        $dv = new DataVerify($data);
        $dv->field('website')->url;
        
        $this->assertTrue($dv->verify());
    }

    public function testDefaultHttpUrl(): void
    {
        $data = (object)['website' => 'http://example.com'];
        $dv = new DataVerify($data);
        $dv->field('website')->url;
        
        $this->assertTrue($dv->verify());
    }

    public function testDefaultRejectsFtp(): void
    {
        $data = (object)['website' => 'ftp://files.example.com'];
        $dv = new DataVerify($data);
        $dv->field('website')->url;
        
        $this->assertFalse($dv->verify());
    }

    public function testDefaultRejectsNoTld(): void
    {
        $data = (object)['website' => 'http://intranet'];
        $dv = new DataVerify($data);
        $dv->field('website')->url;
        
        $this->assertFalse($dv->verify());
    }


    public function testCustomSchemesAllowsFtp(): void
    {
        $data = (object)['url' => 'ftp://files.example.com'];
        $dv = new DataVerify($data);
        $dv->field('url')->url(['ftp', 'ftps']);
        
        $this->assertTrue($dv->verify());
    }

    public function testCustomSchemesAllowsWebSocket(): void
    {
        $data = (object)['url' => 'ws://socket.example.com'];
        $dv = new DataVerify($data);
        $dv->field('url')->url(['ws', 'wss']);
        
        $this->assertTrue($dv->verify());
    }

    public function testCustomSchemesAllowsSecureWebSocket(): void
    {
        $data = (object)['url' => 'wss://secure.socket.example.com'];
        $dv = new DataVerify($data);
        $dv->field('url')->url(['ws', 'wss']);
        
        $this->assertTrue($dv->verify());
    }

    public function testCustomSchemesRejectsUnlisted(): void
    {
        $data = (object)['url' => 'http://example.com'];
        $dv = new DataVerify($data);
        $dv->field('url')->url(['ftp']);
        
        $this->assertFalse($dv->verify());
    }

    public function testCustomSchemesCaseInsensitive(): void
    {
        $data = (object)['url' => 'HTTPS://EXAMPLE.COM'];
        $dv = new DataVerify($data);
        $dv->field('url')->url(['HtTp', 'HtTpS']);
        
        $this->assertTrue($dv->verify());
    }

    public function testCustomSchemesMultiple(): void
    {
        $data = (object)['url' => 'sftp://secure.files.com'];
        $dv = new DataVerify($data);
        $dv->field('url')->url(['http', 'https', 'ftp', 'ftps', 'sftp']);
        
        $this->assertTrue($dv->verify());
    }

    public function testRequireTldTrueRejectsLocalhost(): void
    {
        $data = (object)['url' => 'http://localhost'];
        $dv = new DataVerify($data);
        $dv->field('url')->url(['http'], requireTld: true);
        
        $this->assertFalse($dv->verify());
    }

    public function testRequireTldFalseAllowsLocalhost(): void
    {
        $data = (object)['url' => 'http://localhost'];
        $dv = new DataVerify($data);
        $dv->field('url')->url(['http'], requireTld: false);
        
        $this->assertTrue($dv->verify());
    }

    public function testRequireTldFalseAllowsIntranet(): void
    {
        $data = (object)['url' => 'http://intranet'];
        $dv = new DataVerify($data);
        $dv->field('url')->url(['http'], requireTld: false);
        
        $this->assertTrue($dv->verify());
    }

    public function testRequireTldTrueAllowsIpAddress(): void
    {
        // IP addresses are always allowed even with requireTld=true
        $data = (object)['url' => 'http://192.168.1.1'];
        $dv = new DataVerify($data);
        $dv->field('url')->url(['http'], requireTld: true);
        
        $this->assertTrue($dv->verify());
    }

    public function testRequireTldTrueAllowsIpv6(): void
    {
        $data = (object)['url' => 'http://[::1]'];
        $dv = new DataVerify($data);
        $dv->field('url')->url(['http'], requireTld: true);
        
        $result = $dv->verify();
        
        $this->assertTrue($result);
    }

    public function testRequireTldFalseAllowsSingleWord(): void
    {
        $data = (object)['url' => 'http://server'];
        $dv = new DataVerify($data);
        $dv->field('url')->url(['http'], requireTld: false);
        
        $this->assertTrue($dv->verify());
    }

    public function testRequireTldTrueRejectsSingleWord(): void
    {
        $data = (object)['url' => 'http://server'];
        $dv = new DataVerify($data);
        $dv->field('url')->url(['http'], requireTld: true);
        
        $this->assertFalse($dv->verify());
    }

    public function testCustomSchemeWithoutTld(): void
    {
        $data = (object)['url' => 'ws://localhost'];
        $dv = new DataVerify($data);
        $dv->field('url')->url(['ws', 'wss'], requireTld: false);
        
        $this->assertTrue($dv->verify());
    }

    public function testMultipleSchemesWithoutTld(): void
    {
        $data = (object)['url' => 'ftp://intranet-files'];
        $dv = new DataVerify($data);
        $dv->field('url')->url(['http', 'https', 'ftp'], requireTld: false);
        
        $this->assertTrue($dv->verify());
    }

    public function testBlocksJavascriptByDefault(): void
    {
        $data = (object)['url' => 'javascript:alert(1)'];
        $dv = new DataVerify($data);
        $dv->field('url')->url();
        
        $this->assertFalse($dv->verify());
    }

    public function testBlocksDataByDefault(): void
    {
        $data = (object)['url' => 'data:text/html,<script>alert(1)</script>'];
        $dv = new DataVerify($data);
        $dv->field('url')->url();
        
        $this->assertFalse($dv->verify());
    }

    public function testBlocksFileByDefault(): void
    {
        $data = (object)['url' => 'file:///etc/passwd'];
        $dv = new DataVerify($data);
        $dv->field('url')->url();
        
        $this->assertFalse($dv->verify());
    }

    public function testApiEndpointWithCustomSchemes(): void
    {
        $data = (object)['api_url' => 'https://api.example.com/v1/users'];
        $dv = new DataVerify($data);
        $dv->field('api_url')->url(['http', 'https']);
        
        $this->assertTrue($dv->verify());
    }

    public function testWebSocketConnectionUrl(): void
    {
        $data = (object)['ws_url' => 'wss://chat.example.com/socket'];
        $dv = new DataVerify($data);
        $dv->field('ws_url')->url(['ws', 'wss']);
        
        $this->assertTrue($dv->verify());
    }

    public function testInternalToolWithoutTld(): void
    {
        $data = (object)['tool_url' => 'http://jenkins:8080'];
        $dv = new DataVerify($data);
        $dv->field('tool_url')->url(['http'], requireTld: false);
        
        $this->assertTrue($dv->verify());
    }

    public function testDevelopmentEnvironment(): void
    {
        $data = (object)['dev_url' => 'http://localhost:3000'];
        $dv = new DataVerify($data);
        $dv->field('dev_url')->url(['http'], requireTld: false);
        
        $this->assertTrue($dv->verify());
    }

    public function testErrorMessageOnInvalidScheme(): void
    {
        $data = (object)['url' => 'ftp://example.com'];
        $dv = new DataVerify($data);
        $dv->field('url')->url(['http', 'https']);
        
        $this->assertFalse($dv->verify());
        
        $errors = $dv->getErrors();
        $this->assertEquals('url', $errors[0]['test']);
    }

    public function testErrorMessageOnMissingTld(): void
    {
        $data = (object)['url' => 'http://intranet'];
        $dv = new DataVerify($data);
        $dv->field('url')->url(['http'], requireTld: true);
        
        $this->assertFalse($dv->verify());
        
        $errors = $dv->getErrors();
        $this->assertEquals('url', $errors[0]['test']);
    }

    public function testEmptyStringWithoutRequired(): void
    {
        $data = (object)['url' => ''];
        $dv = new DataVerify($data);
        $dv->field('url')->url();
        
        $this->assertTrue($dv->verify());
    }

    public function testNullWithoutRequired(): void
    {
        $data = (object)['url' => null];
        $dv = new DataVerify($data);
        $dv->field('url')->url();
        
        $this->assertTrue($dv->verify());
    }

    public function testInvalidTypeArray(): void
    {
        $data = (object)['url' => ['https://example.com']];
        $dv = new DataVerify($data);
        $dv->field('url')->url();
        
        $this->assertFalse($dv->verify());
    }

    public function testInvalidTypeInteger(): void
    {
        $data = (object)['url' => 12345];
        $dv = new DataVerify($data);
        $dv->field('url')->url();
        
        $this->assertFalse($dv->verify());
    }

    public function testConditionalUrlWithCustomSchemes(): void
    {
        $data = (object)[
            'protocol' => 'websocket',
            'endpoint' => 'wss://chat.example.com'
        ];
        
        $dv = new DataVerify($data);
        $dv->field('endpoint')
            ->when('protocol', '=', 'websocket')
            ->then->url(['ws', 'wss']);
        
        $this->assertTrue($dv->verify());
    }

    public function testConditionalUrlWithoutTld(): void
    {
        $data = (object)[
            'environment' => 'development',
            'url' => 'http://localhost:3000'
        ];
        
        $dv = new DataVerify($data);
        $dv->field('url')
            ->when('environment', '=', 'development')
            ->then->url(['http'], requireTld: false);
        
        $this->assertTrue($dv->verify());
    }
}
