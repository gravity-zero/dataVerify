<?php

use Gravity\DataVerify;

class DetailedBench
{
    private object $data;
    private DataVerify $verifier;
    
    public function setUp(): void
    {
        $this->data = new stdClass();
        $this->data->email = "test@example.com";
        $this->data->age = 25;
    }
    
    /**
     * @BeforeMethods({"setUp"})
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchInstantiationOnly(): void
    {
        $verifier = new DataVerify($this->data);
    }
    
    /**
     * @BeforeMethods({"setUp"})
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchFieldCallOnly(): void
    {
        $verifier = new DataVerify($this->data);
        $verifier->field('email');
    }
    
    /**
     * @BeforeMethods({"setUp"})
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchFieldPlusRequired(): void
    {
        $verifier = new DataVerify($this->data);
        $verifier->field('email')->required;
    }
    
    /**
     * @BeforeMethods({"setUp"})
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchFieldChain(): void
    {
        $verifier = new DataVerify($this->data);
        $verifier->field('email')->required->email;
    }
    
    /**
     * @BeforeMethods({"setUp"})
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchTwoFields(): void
    {
        $verifier = new DataVerify($this->data);
        $verifier
            ->field('email')->required->email
            ->field('age')->required->int;
    }
    
    /**
     * @BeforeMethods({"setUp"})
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchFieldsWithArgs(): void
    {
        $verifier = new DataVerify($this->data);
        $verifier
            ->field('email')->required->email
            ->field('age')->required->int->between(18, 100);
    }
    
    /**
     * @BeforeMethods({"setUp"})
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchVerifyOnly(): void
    {
        $verifier = new DataVerify($this->data);
        $verifier
            ->field('email')->required->email
            ->field('age')->required->int->between(18, 100);
        
        $verifier->verify();
    }
    
    /**
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchFullCycle(): void
    {
        $data = new stdClass();
        $data->email = "test@example.com";
        $data->age = 25;
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('email')->required->email
            ->field('age')->required->int->between(18, 100);
        
        $verifier->verify();
    }
}