<?php

use Gravity\DataVerify;
use Gravity\Interfaces\ValidationStrategyInterface;

class DataVerifyBench
{
    private static ?ValidationStrategyInterface $globalStrategy = null;
    
    public function initGlobalStrategy(): void
    {
        if (self::$globalStrategy === null) {
            self::$globalStrategy = new class implements ValidationStrategyInterface {
                public function getName(): string { return 'test_global'; }
                public function execute(mixed $value, array $args): bool { return true; }
            };
            DataVerify::global()->register(self::$globalStrategy);
        }
    }
    
    /**
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchSimpleValidation(): void
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
    
    /**
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchComplexValidation(): void
    {
        $data = new stdClass();
        $data->user = new stdClass();
        $data->user->name = "John Doe";
        $data->user->email = "john@example.com";
        $data->user->profile = new stdClass();
        $data->user->profile->age = 30;
        $data->user->profile->bio = "Software developer";
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('user')->required->object
                ->subfield('name')->required->string->minLength(3)->maxLength(50)
                ->subfield('email')->required->email
                ->subfield('profile')->required->object;
        
        $verifier->verify();
    }
    
    /**
     * @Revs(100)
     * @Iterations(5)
     */
    public function benchBatchMode(): void
    {
        $data = new stdClass();
        for ($i = 0; $i < 100; $i++) {
            $data->{"field{$i}"} = "invalid_email";
        }
        
        $verifier = new DataVerify($data);
        for ($i = 0; $i < 100; $i++) {
            $verifier->field("field{$i}")->required->email;
        }
        
        $verifier->verify(batch: true);
    }
    
    /**
     * @Revs(100)
     * @Iterations(5)
     */
    public function benchFailFastMode(): void
    {
        $data = new stdClass();
        for ($i = 0; $i < 100; $i++) {
            $data->{"field{$i}"} = "invalid_email";
        }
        
        $verifier = new DataVerify($data);
        for ($i = 0; $i < 100; $i++) {
            $verifier->field("field{$i}")->required->email;
        }
        
        $verifier->verify(batch: false);
    }
    
    /**
     * @Revs(500)
     * @Iterations(5)
     */
    public function benchCustomStrategy(): void
    {
        $isPalindrome = new class implements ValidationStrategyInterface {
            public function execute(mixed $value, array $args): bool {
                if (!is_string($value)) return false;
                return $value === strrev($value);
            }
            public function getName(): string {
                return 'isPalindrome';
            }
        };
        
        $data = new stdClass();
        $data->word = "radar";
        
        $verifier = new DataVerify($data);
        $verifier->registerStrategy($isPalindrome);
        $verifier->field('word')->isPalindrome();
        $verifier->verify();
    }

    /**
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchConditionalValidationTriggered(): void
    {
        $data = new stdClass();
        $data->delivery_type = 'shipping';
        $data->shipping_address = '123 Main St';
        $data->country = 'FR';
        $data->vat_number = 'FR12345678901';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('shipping_address')
            ->when('delivery_type', '=', 'shipping')
            ->then->required->string
            
            ->field('vat_number')
            ->when('country', 'in', ['FR', 'DE', 'IT'])
            ->then->required->string;
        
        $verifier->verify();
    }

    /**
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchConditionalValidationNotTriggered(): void
    {
        $data = new stdClass();
        $data->delivery_type = 'pickup';
        $data->shipping_address = '';
        $data->country = 'US';
        $data->vat_number = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('shipping_address')
            ->when('delivery_type', '=', 'shipping')
            ->then->required->string
            
            ->field('vat_number')
            ->when('country', 'in', ['FR', 'DE', 'IT'])
            ->then->required->string;
        
        $verifier->verify();
    }
    
    /**
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchConditionalValidationFailed(): void
    {
        $data = new stdClass();
        $data->delivery_type = 'shipping';
        $data->shipping_address = '';
        $data->country = 'FR';
        $data->vat_number = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('shipping_address')
            ->when('delivery_type', '=', 'shipping')
            ->then->required->string
            
            ->field('vat_number')
            ->when('country', 'in', ['FR', 'DE', 'IT'])
            ->then->required->string;
        
        $verifier->verify();
        $verifier->getErrors();
    }

    /**
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchMixNormalAndConditional(): void
    {
        $data = new stdClass();
        $data->email = "test@example.com";
        $data->age = 25;
        $data->newsletter = true;
        $data->phone = "+33612345678";
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('email')->required->email
            ->field('age')->required->int->between(18, 100)
            
            ->field('phone')
            ->when('newsletter', '=', true)
            ->then->required->string;
        
        $verifier->verify();
    }

    /**
     * @Revs(500)
     * @Iterations(5)
     */
    public function benchConditionalOnSubfields(): void
    {
        $data = new stdClass();
        $data->user = new stdClass();
        $data->user->type = 'business';
        $data->user->company_name = 'Acme Corp';
        $data->user->vat_number = 'FR12345678901';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('user')->required->object
                ->subfield('company_name')
                ->when('user.type', '=', 'business')
                ->then->required->string
                
                ->subfield('vat_number')
                ->when('user.type', '=', 'business')
                ->then->required->string;
        
        $verifier->verify();
    }

    /**
     * @BeforeMethods({"initGlobalStrategy"})
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchGlobalStrategyExecution(): void
    {
        $data = new stdClass();
        $data->field = 'value';
        
        $dv = new DataVerify($data);
        $dv->field('field')->test_global;
        $dv->verify();
    }

    /**
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchInstanceStrategy(): void
    {
        $strategy = new class implements ValidationStrategyInterface {
            public function getName(): string { return 'test_instance'; }
            public function execute(mixed $value, array $args): bool { return true; }
        };
        
        $data = new stdClass();
        $data->field = 'value';
        
        $dv = new DataVerify($data);
        $dv->registerStrategy($strategy);
        $dv->field('field')->test_instance;
        $dv->verify();
    }

    /**
     * @Revs(500)
     * @Iterations(5)
     */
    public function benchComplexConditionalAND(): void
    {
        $data = new stdClass();
        $data->type = 'premium';
        $data->amount = 150;
        $data->country = 'FR';
        $data->discount = 'CODE123';
        
        $dv = new DataVerify($data);
        $dv->field('discount')
           ->when('type', '=', 'premium')
           ->and('amount', '>', 100)
           ->and('country', 'in', ['FR', 'DE'])
           ->then->required->string;
        
        $dv->verify();
    }

    /**
     * @Revs(500)
     * @Iterations(5)
     */
    public function benchComplexConditionalOR(): void
    {
        $data = new stdClass();
        $data->country = 'BE';
        $data->vat_number = 'BE0123456789';
        
        $dv = new DataVerify($data);
        $dv->field('vat_number')
           ->when('country', '=', 'FR')
           ->or('country', '=', 'BE')
           ->or('country', '=', 'DE')
           ->then->required->string;
        
        $dv->verify();
    }

    /**
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchTranslationWithErrorRendering(): void
    {
        $data = new stdClass();
        $data->email = 'invalid';
        
        $dv = new DataVerify($data);
        $dv->setLocale('fr');
        $dv->field('email')->required->email;
        
        $dv->verify();
        $dv->getErrors();
    }

    /**
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchNoTranslationWithErrorRendering(): void
    {
        $data = new stdClass();
        $data->email = 'invalid';
        
        $dv = new DataVerify($data);
        $dv->field('email')->required->email;
        
        $dv->verify();
        $dv->getErrors();
    }

    /**
     * @Revs(500)
     * @Iterations(5)
     */
    public function benchDeeplyNestedConditional(): void
    {
        $data = new stdClass();
        $data->config = new stdClass();
        $data->config->features = new stdClass();
        $data->config->features->advanced = new stdClass();
        $data->config->features->advanced->enabled = true;
        $data->config->mode = 'production';
        $data->config->features->advanced->api_key = 'secret123';
        
        $dv = new DataVerify($data);
        $dv->field('config')->required->object
           ->subfield('features')->required->object
              ->subfield('features', 'advanced')->required->object
                 ->subfield('features', 'advanced', 'api_key')
                 ->when('config.features.advanced.enabled', '=', true)
                 ->and('config.mode', '=', 'production')
                 ->then->required->string;
        
        $dv->verify();
    }

    /**
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchErrorMessageCustomization(): void
    {
        $data = new stdClass();
        $data->email = 'invalid';
        
        $dv = new DataVerify($data);
        $dv->field('email')
           ->required
           ->email
           ->errorMessage('Please provide a valid professional email address');
        
        $dv->verify();
        $dv->getErrors();
    }

    /**
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchAliasUsage(): void
    {
        $data = new stdClass();
        $data->user_email = 'invalid';
        
        $dv = new DataVerify($data);
        $dv->field('user_email')
           ->alias('Email Address')
           ->required
           ->email;
        
        $dv->verify();
        $dv->getErrors();
    }

    /**
     * @Revs(500)
     * @Iterations(5)
     */
    public function benchMultipleFieldsSameCondition(): void
    {
        $data = new stdClass();
        $data->status = 'pending';
        $data->priority = 'high';
        $data->approval = 'approved';
        
        $dv = new DataVerify($data);
        $dv->field('approval')
           ->when('status', '=', 'pending')
           ->then->required;
        
        $dv->field('approval')
           ->when('priority', '=', 'high')
           ->then->required;
        
        $dv->verify();
    }

    /**
     * @Revs(100)
     * @Iterations(5)
     */
    public function benchLoadFromDirectory(): void
    {
        $strategiesLoaded = 0;
        
        try {
            $strategiesLoaded = DataVerify::global()->loadFromDirectory(
                __DIR__ . '/../tests/fixtures/strategies',
                'Tests\\Fixtures\\Strategies'
            );
        } catch (\Exception $e) {
            if ($strategiesLoaded === 0) {
                throw new \RuntimeException('benchLoadFromDirectory: No strategies loaded, benchmark invalid');
            }
        }
        
        $data = new stdClass();
        $data->test = 'value';
        
        $dv = new DataVerify($data);
        $dv->field('test')->string;
        $dv->verify();
    }
}