<?php

use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;

class FileMimeTest extends TestCase
{
    private function createTempTextFile(): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'dv_');
        file_put_contents($tmpFile, "just some text\n");
        return $tmpFile;
    }

    public function testFileMimeWithValidSingleMime()
    {
        $path = $this->createTempTextFile();
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($path);
        
        $data = new stdClass();
        $data->path = $path;
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('path')->required->fileMime($mime);
        
        $this->assertTrue($verifier->verify());
        
        @unlink($path);
    }

    public function testFileMimeWithValidMimeInArray()
    {
        $path = $this->createTempTextFile();
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($path);
        
        $data = new stdClass();
        $data->path = $path;
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('path')->required->fileMime([$mime, 'application/octet-stream']);
        
        $this->assertTrue($verifier->verify());
        
        @unlink($path);
    }

    public function testFileMimeWithInvalidMime()
    {
        $path = $this->createTempTextFile();
        
        $data = new stdClass();
        $data->path = $path;
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('path')->required->fileMime('application/json');
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        $this->assertStringContainsString('application/json', $errors[0]['message']);
        
        @unlink($path);
    }

    public function testFileMimeWithMissingFile()
    {
        $data = new stdClass();
        $data->path = '/this/does/not/exist/'.uniqid('dv_', true);
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('path')->required->fileMime('text/plain');
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        $this->assertStringContainsString('text/plain', $errors[0]['message']);
    }
}