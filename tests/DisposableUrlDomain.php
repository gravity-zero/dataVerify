<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;


class DisposableUrlDomain extends TestCase
{

    public function testRejectsBitly(): void
    {
        $data = (object)['url' => 'https://bit.ly/abc123'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
    }

    public function testRejectsTinyUrl(): void
    {
        $data = (object)['url' => 'http://tinyurl.com/xyz'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
    }

    public function testRejectsGooGl(): void
    {
        $data = (object)['url' => 'https://goo.gl/maps/xyz'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
    }

    public function testRejectsIsGd(): void
    {
        $data = (object)['url' => 'http://is.gd/short'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
    }

    public function testRejectsTCo(): void
    {
        $data = (object)['url' => 'https://t.co/abc123'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
    }

    public function testRejectsFileIo(): void
    {
        $data = (object)['url' => 'https://file.io/abc123'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
    }

    public function testRejectsTransferSh(): void
    {
        $data = (object)['url' => 'https://transfer.sh/xyz/file.pdf'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
    }

    public function testRejectsCatbox(): void
    {
        $data = (object)['url' => 'https://catbox.moe/c/abc123'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
    }

    public function testRejectsUguuSe(): void
    {
        $data = (object)['url' => 'https://uguu.se/xyz'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
    }

    public function testRejectsNgrok(): void
    {
        $data = (object)['url' => 'https://abc123.ngrok.io'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
    }

    public function testRejectsNgrokFree(): void
    {
        $data = (object)['url' => 'https://xyz.ngrok-free.app'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
    }

    public function testRejectsLocalTunnel(): void
    {
        $data = (object)['url' => 'https://myapp.localtunnel.me'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
    }

    public function testRejects000Webhost(): void
    {
        $data = (object)['url' => 'https://mysite.000webhostapp.com'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
    }

    public function testRejectsByethost(): void
    {
        $data = (object)['url' => 'http://mysite.byethost.com'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
    }

    public function testAcceptsGoogleCom(): void
    {
        $data = (object)['url' => 'https://www.google.com'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertTrue($dv->verify());
    }

    public function testAcceptsGithubCom(): void
    {
        $data = (object)['url' => 'https://github.com/user/repo'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertTrue($dv->verify());
    }

    public function testAcceptsCompanyWebsite(): void
    {
        $data = (object)['url' => 'https://www.example.com'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertTrue($dv->verify());
    }

    public function testAcceptsSubdomain(): void
    {
        $data = (object)['url' => 'https://api.example.com'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertTrue($dv->verify());
    }

    public function testAcceptsDeepSubdomain(): void
    {
        $data = (object)['url' => 'https://api.v2.example.com'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertTrue($dv->verify());
    }

    public function testCustomDisposableListRejects(): void
    {
        $data = (object)['url' => 'https://suspicious-site.com'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain(['suspicious-site.com', 'blocked.com']);
        
        $this->assertFalse($dv->verify());
    }

    public function testCustomDisposableListAccepts(): void
    {
        $data = (object)['url' => 'https://bit.ly/abc123'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain(['only-this-domain.com']);
        
        $this->assertTrue($dv->verify());
    }

    public function testCustomListWithSubdomain(): void
    {
        $data = (object)['url' => 'https://subdomain.blocked.com'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain(['blocked.com']);
        
        $this->assertFalse($dv->verify());
    }

    public function testCaseInsensitiveDomain(): void
    {
        $data = (object)['url' => 'https://BIT.LY/xyz'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
    }

    public function testCaseInsensitiveCustomList(): void
    {
        $data = (object)['url' => 'https://BLOCKED.COM'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain(['blocked.com']);
        
        $this->assertFalse($dv->verify());
    }

    public function testSubdomainOfDisposableDomain(): void
    {
        $data = (object)['url' => 'https://xyz.ngrok.io'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
    }

    public function testDeepSubdomainOfDisposableDomain(): void
    {
        $data = (object)['url' => 'https://deep.sub.ngrok.io'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
    }

    public function testExactMatchOnly(): void
    {
        $data = (object)['url' => 'https://notsimilar.com'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain(['similar.com']);
        
        $this->assertTrue($dv->verify());
    }

    public function testRejectsInvalidUrl(): void
    {
        $data = (object)['url' => 'not-a-url'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
    }

    public function testRejectsEmptyString(): void
    {
        $data = (object)['url' => ''];
        $dv = new DataVerify($data);
        $dv->field('url')->required->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
    }

    public function testRejectsUrlWithoutHost(): void
    {
        $data = (object)['url' => 'http://'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
    }

    public function testRejectsNonString(): void
    {
        $data = (object)['url' => 12345];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
    }

    public function testRejectsArray(): void
    {
        $data = (object)['url' => ['https://example.com']];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
    }

    public function testOptionalFieldWithNull(): void
    {
        $data = (object)['url' => null];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertTrue($dv->verify());
    }

    public function testOptionalFieldMissing(): void
    {
        $data = (object)[];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertTrue($dv->verify());
    }

    public function testCombinedWithUrlValidation(): void
    {
        $data = (object)['website' => 'https://example.com'];
        $dv = new DataVerify($data);
        $dv->field('website')->url()->disposableUrlDomain;
        
        $this->assertTrue($dv->verify());
    }

    public function testCombinedUrlAndDisposableBothFail(): void
    {
        $data = (object)['website' => 'https://bit.ly/abc'];
        $dv = new DataVerify($data);
        $dv->field('website')->url()->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
        
        $errors = $dv->getErrors();
        $this->assertEquals('disposableUrlDomain', $errors[0]['test']);
    }

    public function testCombinedWithRequired(): void
    {
        $data = (object)['website' => 'https://example.com'];
        $dv = new DataVerify($data);
        $dv->field('website')->required->disposableUrlDomain;
        
        $this->assertTrue($dv->verify());
    }

    public function testCombinedRequiredAndDisposable(): void
    {
        $data = (object)['website' => 'https://bit.ly/xyz'];
        $dv = new DataVerify($data);
        $dv->field('website')->required->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
    }

    public function testErrorMessageOnDisposableDomain(): void
    {
        $data = (object)['url' => 'https://bit.ly/abc'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
        
        $errors = $dv->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals('url', $errors[0]['field']);
        $this->assertEquals('disposableUrlDomain', $errors[0]['test']);
    }

    public function testCustomErrorMessage(): void
    {
        $data = (object)['url' => 'https://bit.ly/abc'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain()->errorMessage('URL shorteners are not allowed');
        
        $this->assertFalse($dv->verify());
        
        $errors = $dv->getErrors();
        $this->assertEquals('URL shorteners are not allowed', $errors[0]['message']);
    }

    public function testErrorMessageWithAlias(): void
    {
        $data = (object)['url' => 'https://bit.ly/abc'];
        $dv = new DataVerify($data);
        $dv->field('url')->alias('Company Website')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
        
        $errors = $dv->getErrors();
        $this->assertEquals('Company Website', $errors[0]['alias']);
    }

    public function testConditionalDisposableCheck(): void
    {
        $data = (object)[
            'url_type' => 'permanent',
            'url' => 'https://bit.ly/abc'
        ];
        
        $dv = new DataVerify($data);
        $dv->field('url')
            ->when('url_type', '=', 'permanent')
            ->then->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
    }

    public function testConditionalDisposableSkipped(): void
    {
        $data = (object)[
            'url_type' => 'temporary',
            'url' => 'https://bit.ly/abc'
        ];
        
        $dv = new DataVerify($data);
        $dv->field('url')
            ->when('url_type', '=', 'permanent')
            ->then->disposableUrlDomain;
        
        $this->assertTrue($dv->verify());
    }

    public function testDisposableInNestedObject(): void
    {
        $data = (object)[
            'profile' => (object)[
                'website' => 'https://bit.ly/profile'
            ]
        ];
        
        $dv = new DataVerify($data);
        $dv->field('profile')->object
            ->subfield('website')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
        
        $errors = $dv->getErrors();
        $this->assertEquals('profile.website', $errors[0]['field']);
    }

    public function testBatchModeMultipleDisposableErrors(): void
    {
        $data = (object)[
            'url1' => 'https://bit.ly/abc',
            'url2' => 'https://tinyurl.com/xyz',
            'url3' => 'https://example.com'
        ];
        
        $dv = new DataVerify($data);
        $dv->field('url1')->disposableUrlDomain()
           ->field('url2')->disposableUrlDomain()
           ->field('url3')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
        
        $errors = $dv->getErrors();
        $this->assertCount(2, $errors);
    }

    public function testFailFastMode(): void
    {
        $data = (object)[
            'url1' => 'https://bit.ly/abc',
            'url2' => 'https://tinyurl.com/xyz'
        ];
        
        $dv = new DataVerify($data);
        $dv->field('url1')->disposableUrlDomain()
           ->field('url2')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify(batch: false));
        
        $errors = $dv->getErrors();
        $this->assertCount(1, $errors);
    }

    public function testUrlWithPath(): void
    {
        $data = (object)['url' => 'https://bit.ly/path/to/resource'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
    }

    public function testUrlWithQueryString(): void
    {
        $data = (object)['url' => 'https://bit.ly/abc?param=value'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
    }

    public function testUrlWithFragment(): void
    {
        $data = (object)['url' => 'https://bit.ly/abc#section'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
    }

    public function testUrlWithPort(): void
    {
        $data = (object)['url' => 'https://ngrok.io:8080'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
    }

    public function testUrlWithAuthentication(): void
    {
        $data = (object)['url' => 'https://user:pass@bit.ly/abc'];
        $dv = new DataVerify($data);
        $dv->field('url')->disposableUrlDomain;
        
        $this->assertFalse($dv->verify());
    }
}
