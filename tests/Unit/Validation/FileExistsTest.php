<?php

use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;

class FileExistsTest extends TestCase
{
    public function testFileExistsWithRealFile()
    {
        $tmpDir  = sys_get_temp_dir();
        $tmpFile = tempnam($tmpDir, 'dv_');
        file_put_contents($tmpFile, 'dummy content');
        
        $data = new stdClass();
        $data->path = $tmpFile;
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('path')->required->fileExists;
        
        $this->assertTrue($verifier->verify());
        
        @unlink($tmpFile);
    }

    public function testFileExistsWithMissingFile()
    {
        $data = new stdClass();
        $data->path = '/this/does/not/exist/'.uniqid('dv_', true);
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('path')->required->fileExists;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        $this->assertSame("The file path does not exist", $errors[0]['message']);
    }

    public function testFileExistsWithDirectory()
    {
        $dir = sys_get_temp_dir();
        
        $data = new stdClass();
        $data->path = $dir;
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('path')->required->fileExists;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        $this->assertSame("The file path does not exist", $errors[0]['message']);
    }
}