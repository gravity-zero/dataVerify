<?php

use Gravity\DataVerify;

class ErrorCreationBench
{
    private object $validData;
    private object $invalidData;
    
    public function setUp(): void
    {
        $this->validData = new stdClass();
        $this->validData->email = "test@example.com";
        $this->validData->age = 25;
        
        $this->invalidData = new stdClass();
        $this->invalidData->email = "invalid";
        $this->invalidData->age = -5;
    }
    
    /**
     * @BeforeMethods({"setUp"})
     * @Revs(1000)
     * @Iterations(10)
     */
    public function benchNoErrors(): void
    {
        $verifier = new DataVerify($this->validData);
        $verifier
            ->field('email')->required->email
            ->field('age')->required->int->between(18, 100);
        
        $verifier->verify();
    }
    
    /**
     * @BeforeMethods({"setUp"})
     * @Revs(1000)
     * @Iterations(10)
     */
    public function benchWithErrors(): void
    {
        $verifier = new DataVerify($this->invalidData);
        $verifier
            ->field('email')->required->email
            ->field('age')->required->int->between(18, 100);
        
        $verifier->verify();
    }
    
    /**
     * @BeforeMethods({"setUp"})
     * @Revs(1000)
     * @Iterations(10)
     */
    public function benchWithErrorsAndGetErrors(): void
    {
        $verifier = new DataVerify($this->invalidData);
        $verifier
            ->field('email')->required->email
            ->field('age')->required->int->between(18, 100);
        
        $verifier->verify();
        $verifier->getErrors();
    }
    
    /**
     * @BeforeMethods({"setUp"})
     * @Revs(1000)
     * @Iterations(10)
     */
    public function benchCustomErrorMessage(): void
    {
        $data = new stdClass();
        $data->email = "invalid";
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('email')
            ->required
            ->email
            ->errorMessage('Custom error');
        
        $verifier->verify();
    }
    
    /**
     * @BeforeMethods({"setUp"})
     * @Revs(1000)
     * @Iterations(10)
     */
    public function benchNoTranslation(): void
    {
        $verifier = new DataVerify($this->invalidData);
        $verifier
            ->field('email')->required->email
            ->field('age')->required->int->between(18, 100);
        
        $verifier->verify();
    }
    
    /**
     * @BeforeMethods({"setUp"})
     * @Revs(1000)
     * @Iterations(10)
     */
    public function benchWithTranslation(): void
    {
        $verifier = new DataVerify($this->invalidData);
        $verifier->setLocale('fr');
        $verifier
            ->field('email')->required->email
            ->field('age')->required->int->between(18, 100);
        
        $verifier->verify();
    }

}