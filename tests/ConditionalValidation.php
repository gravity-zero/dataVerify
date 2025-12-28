<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Gravity\DataVerify;
use PHPUnit\Framework\TestCase;

class ConditionalValidation extends TestCase
{
    public function testSimpleConditionalValidation(): void
    {
        $data = new stdClass();
        $data->delivery_type = 'shipping';
        $data->shipping_address = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('shipping_address')
            ->when('delivery_type', '=', 'shipping')
            ->then->required;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals('shipping_address', $errors[0]['field']);
    }

    public function testThenWithoutWhen(): void
    {
        $data = new stdClass();
        $data->field = '';
        
        $verifier = new DataVerify($data);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Cannot use 'then' without 'when'");
        
        $verifier
            ->field('field')
            ->then->required;
    }

    public function testConditionalValidationNotTriggered(): void
    {
        $data = new stdClass();
        $data->delivery_type = 'pickup';
        $data->shipping_address = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('shipping_address')
            ->when('delivery_type', '=', 'shipping')
            ->then->required;
        
        $this->assertTrue($verifier->verify());
    }

    public function testConditionalPathResolution(): void
    {
        $data = new stdClass();
        $data->config = new stdClass();
        $data->config->features = new stdClass();
        $data->config->features->enabled = true;
        $data->config->features->api_key = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('config')->required->object
                ->subfield('features')->required->object
                    ->subfield('features', 'api_key')
                    ->when('config.features.enabled', '=', true)
                    ->then->required->string;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        $this->assertCount(1, $errors);
    }

    public function testConditionalWithAmbiguousPath(): void
    {
        $data = new stdClass();
        $data->type = 'company';
        $data->user = new stdClass();
        $data->user->type = 'personal';
        $data->user->company_name = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('user')->required->object
                ->subfield('company_name')
                ->when('type', '=', 'company')
                ->then->required;
        
        $this->assertFalse($verifier->verify());
    }
    
    public function testMultipleConditionalValidations(): void
    {
        $data = new stdClass();
        $data->delivery_type = 'shipping';
        $data->shipping_address = 'abc';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('shipping_address')
            ->when('delivery_type', '=', 'shipping')
            ->then->required->string->minLength(10);
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals('minLength', $errors[0]['test']);
    }
    
    public function testConditionalWithInOperator(): void
    {
        $data = new stdClass();
        $data->country = 'FR';
        $data->vat_number = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('vat_number')
            ->when('country', 'in', ['FR', 'DE', 'IT'])
            ->then->required;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        $this->assertCount(1, $errors);
    }
    
    public function testConditionalWithNotInOperator(): void
    {
        $data = new stdClass();
        $data->country = 'US';
        $data->vat_number = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('vat_number')
            ->when('country', 'not_in', ['FR', 'DE', 'IT'])
            ->then->required;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        $this->assertCount(1, $errors);
    }
    
    public function testConditionalWithNotEqualsOperator(): void
    {
        $data = new stdClass();
        $data->payment_method = 'card';
        $data->card_number = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('card_number')
            ->when('payment_method', '!=', 'cash')
            ->then->required;
        
        $this->assertFalse($verifier->verify());
    }
    
    public function testConditionalWithNumericOperators(): void
    {
        $data = new stdClass();
        $data->age = 25;
        $data->parental_consent = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('parental_consent')
            ->when('age', '<', 18)
            ->then->required;
        
        $this->assertTrue($verifier->verify());
        
        $data->age = 15;
        $verifier2 = new DataVerify($data);
        $verifier2
            ->field('parental_consent')
            ->when('age', '<', 18)
            ->then->required;
        
        $this->assertFalse($verifier2->verify());
    }
    
    public function testMultipleFieldsWithConditions(): void
    {
        $data = new stdClass();
        $data->has_company = true;
        $data->company_name = '';
        $data->has_vat = true;
        $data->vat_number = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('company_name')
            ->when('has_company', '=', true)
            ->then->required->string
            
            ->field('vat_number')
            ->when('has_vat', '=', true)
            ->then->required->string;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        $this->assertCount(2, $errors);
    }
    
    public function testMixNormalAndConditionalValidations(): void
    {
        $data = new stdClass();
        $data->email = 'invalid';
        $data->is_verified = true;
        $data->verification_code = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('email')
            ->required
            ->email
            
            ->field('verification_code')
            ->when('is_verified', '=', false)
            ->then->required;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertCount(1, $errors);
        $this->assertEquals('email', $errors[0]['field']);
    }
    
    public function testConditionalWithCustomStrategy(): void
    {
        $isPalindrome = new class implements \Gravity\Interfaces\ValidationStrategyInterface {
            public function execute(mixed $value, array $args): bool {
                $minLength = $args[0] ?? 1;
                if (!is_string($value)) return false;
                if (strlen($value) < $minLength) return false;
                return $value === strrev($value);
            }
            
            public function getName(): string {
                return 'isPalindrome';
            }
        };
        
        $data = new stdClass();
        $data->word_type = 'special';
        $data->word = 'test';
        
        $verifier = new DataVerify($data);
        $verifier->registerStrategy($isPalindrome);
        $verifier
            ->field('word')
            ->when('word_type', '=', 'special')
            ->then->isPalindrome(3);
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        $this->assertEquals('isPalindrome', $errors[0]['test']);
    }
    
    public function testConditionalOnSubfield(): void
    {
        $data = new stdClass();
        $data->user = new stdClass();
        $data->user->type = 'company';
        $data->user->company_name = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('user')->required->object
                ->subfield('company_name')
                ->when('user.type', '=', 'company')
                ->then->required->string;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals('user.company_name', $errors[0]['field']);
    }

    public function testConditionalOnMultipleSubfields(): void
    {
        $data = new stdClass();
        $data->order = new stdClass();
        $data->order->type = 'express';
        $data->order->shipping = new stdClass();
        $data->order->shipping->express_carrier = '';
        $data->order->shipping->time_slot = '';
        $data->order->shipping->contact_phone = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('order')->required->object
                ->subfield('shipping')->required->object
                    ->subfield('shipping', 'express_carrier')
                    ->when('order.type', '=', 'express')
                    ->then->required->string
                    
                    ->subfield('shipping', 'time_slot')
                    ->when('order.type', '=', 'express')
                    ->then->required->string
                    
                    ->subfield('shipping', 'contact_phone')
                    ->when('order.type', '=', 'express')
                    ->then->required->string;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertCount(3, $errors);
        
        $fields = array_column($errors, 'field');
        $this->assertContains('order.shipping.express_carrier', $fields);
        $this->assertContains('order.shipping.time_slot', $fields);
        $this->assertContains('order.shipping.contact_phone', $fields);
    }

    public function testConditionalOnSubfieldsWithDifferentConditions(): void
    {
        $data = new stdClass();
        $data->user = new stdClass();
        $data->user->account_type = 'business';
        $data->user->subscription = 'premium';
        $data->user->company_name = '';
        $data->user->vat_number = '';
        $data->user->premium_features = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('user')->required->object
                ->subfield('company_name')
                ->when('user.account_type', '=', 'business')
                ->then->required->string
                
                ->subfield('vat_number')
                ->when('user.account_type', '=', 'business')
                ->then->required->string->minLength(9)
                
                ->subfield('premium_features')
                ->when('user.subscription', '=', 'premium')
                ->then->required->string;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertCount(3, $errors);
        
        $fields = array_column($errors, 'field');
        $this->assertContains('user.company_name', $fields);
        $this->assertContains('user.vat_number', $fields);
        $this->assertContains('user.premium_features', $fields);
    }

    public function testConditionalOnDeeplyNestedSubfields(): void
    {
        $data = new stdClass();
        $data->config = new stdClass();
        $data->config->features = new stdClass();
        $data->config->features->advanced = new stdClass();
        $data->config->features->advanced->enabled = true;
        $data->config->features->advanced->settings = new stdClass();
        $data->config->features->advanced->settings->api_key = '';
        $data->config->features->advanced->settings->webhook_url = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('config')->required->object
                ->subfield('features')->required->object
                    ->subfield('features', 'advanced')->required->object
                        ->subfield('features', 'advanced', 'settings')->required->object
                            ->subfield('features', 'advanced', 'settings', 'api_key')
                            ->when('config.features.advanced.enabled', '=', true)
                            ->then->required->string
                            
                            ->subfield('features', 'advanced', 'settings', 'webhook_url')
                            ->when('config.features.advanced.enabled', '=', true)
                            ->then->required->string;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertCount(2, $errors);
        
        $fields = array_column($errors, 'field');
        $this->assertContains('config.features.advanced.settings.api_key', $fields);
        $this->assertContains('config.features.advanced.settings.webhook_url', $fields);
    }
    
    public function testConditionalWithFailFastMode(): void
    {
        $data = new stdClass();
        $data->type = 'premium';
        $data->field1 = '';
        $data->field2 = '';
        $data->field3 = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('field1')
            ->when('type', '=', 'premium')
            ->then->required
            
            ->field('field2')
            ->when('type', '=', 'premium')
            ->then->required
            
            ->field('field3')
            ->when('type', '=', 'premium')
            ->then->required;
        
        $this->assertFalse($verifier->verify(false));
        $errors = $verifier->getErrors();
        
        $this->assertCount(1, $errors);
        $this->assertEquals('field1', $errors[0]['field']);
    }
    
    public function testConditionalOnNonExistentField(): void
    {
        $data = new stdClass();
        $data->field1 = 'value';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('field1')
            ->when('non_existent_field', '=', 'value')
            ->then->required;
        
        $this->assertTrue($verifier->verify());
    }
    
    public function testConditionalWithGreaterOrEquals(): void
    {
        $data = new stdClass();
        $data->quantity = 100;
        $data->bulk_discount_code = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('bulk_discount_code')
            ->when('quantity', '>=', 100)
            ->then->required;
        
        $this->assertFalse($verifier->verify());
    }
    
    public function testChainingConditionalValidations(): void
    {
        $data = new stdClass();
        $data->country = 'FR';
        $data->company_type = 'B2B';
        $data->vat_number = 'invalid';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('vat_number')
            ->when('country', '=', 'FR')
            ->then->required->string
            
            ->field('vat_number')
            ->when('company_type', '=', 'B2B')
            ->then->regex('/^FR\d{11}$/');
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertGreaterThan(0, count($errors));
    }
    
    public function testMixedValidationsOnSameField(): void
    {
        $data = new stdClass();
        $data->country = 'FR';
        $data->phone = '123';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('phone')
            ->required
            ->string
            ->when('country', '=', 'FR')
            ->then->regex('/^0[1-9]\d{8}$/');
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        $this->assertEquals('regex', $errors[0]['test']);
    }
    
    public function testConditionalWithBoolean(): void
    {
        $data = new stdClass();
        $data->newsletter = true;
        $data->email = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('email')
            ->when('newsletter', '=', true)
            ->then->required->email;
        
        $this->assertFalse($verifier->verify());
    }
    
    public function testConditionalWithAlias(): void
    {
        $data = new stdClass();
        $data->type = 'company';
        $data->company_reg = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('company_reg')
            ->alias('Company Registration Number')
            ->when('type', '=', 'company')
            ->then->required;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        $this->assertEquals('Company Registration Number', $errors[0]['alias']);
    }
    
    public function testConditionalWithCustomErrorMessage(): void
    {
        $data = new stdClass();
        $data->payment = 'card';
        $data->card_number = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('card_number')
            ->when('payment', '=', 'card')
            ->then->required
            ->errorMessage('Card number is mandatory for card payments');
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        $this->assertEquals('Card number is mandatory for card payments', $errors[0]['message']);
    }

    public function testConditionalLogicCombinations(): void
    {
        $data = new stdClass();
        $data->type = 'premium';
        $data->field1 = 'value';
        $data->field2 = '';
        
        $v1 = new DataVerify($data);
        $v1->field('field1')->when('type', '=', 'premium')->then->required;
        $this->assertTrue($v1->verify());
        
        $v2 = new DataVerify($data);
        $v2->field('field2')->when('type', '=', 'premium')->then->required;
        $this->assertFalse($v2->verify());
        
        $data->type = 'basic';
        $v3 = new DataVerify($data);
        $v3->field('field2')->when('type', '=', 'premium')->then->required;
        $this->assertTrue($v3->verify(), 'Condition false, validation should be skipped');
    }

    public function testNormalValidationAfterResetWorks(): void
    {
        $data = new stdClass();
        $data->type = 'premium';
        $data->field1 = 'value';
        $data->field2 = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('field1')
            ->when('type', '=', 'premium')->then->required
            ->field('field2')
            ->required;
        
        $this->assertFalse($verifier->verify());
    }

    public function testNormalValidation2AfterResetWorks(): void
    {
        $data = new stdClass();
        $data->type = 'premium';
        $data->field1 = '24/01/1789';
        $data->field2 = 'here';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('field1')
            ->when('type', '=', 'premium')->then->date('d/m/Y')
            ->field('field2')
            ->required;
        
        $this->assertTrue($verifier->verify());
    }

    public function testVerifyCannotBeCalledTwice(): void
    {
        $data = new stdClass();
        $data->name = 'John';
        
        $verifier = new DataVerify($data);
        $verifier->field('name')->required;
        
        $this->assertTrue($verifier->verify());
        
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('DataVerify instance has already been verified');
        
        $verifier->verify();
    }

    // ===== TESTS POUR TUER LES MUTANTS =====

    /**
     * Tue mutants #1-2 (ligne 189)
     * Test que calling __call() avec pendingConditions mais sans thenMode throw
     */
    public function testCallingValidationMethodWhileInWhenModeThrows(): void
    {
        $data = new stdClass();
        $data->type = 'premium';
        $data->code = 'ABC';

        $dv = new DataVerify($data);
        $dv->field('code')->when('type', '=', 'premium');
        
        // Maintenant en mode when (pendingConditions=true, thenMode=false)
        // Appeler une propriété de validation doit throw
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Incomplete conditional validation");
        
        $dv->string; // ← Trigger __call() ligne 189
    }

    /**
     * Tue mutants #3-7 (ligne 199)
     * Test validation conditionnelle ne s'exécute QUE si thenMode ET pendingConditions
     */
    public function testConditionalExecutesOnlyWithBothFlags(): void
    {
        $data = new stdClass();
        $data->type = 'basic';
        $data->code = null; // Invalid si validé

        $dv = new DataVerify($data);
        $dv->field('code')
            ->when('type', '=', 'premium')  // Condition false
            ->then->required->string;

        // Validation ne doit PAS s'exécuter car condition false
        $this->assertTrue($dv->verify());
    }

    /**
     * Tue mutants #3-7 (ligne 199) - cas inverse
     * Test validation s'exécute bien quand thenMode ET pendingConditions = true
     */
    public function testConditionalExecutesWhenBothFlagsTrue(): void
    {
        $data = new stdClass();
        $data->type = 'premium';
        $data->code = ''; // Invalid

        $dv = new DataVerify($data);
        $dv->field('code')
            ->when('type', '=', 'premium')  // Condition true
            ->then->required;

        // Validation DOIT s'exécuter
        $this->assertFalse($dv->verify());
        $errors = $dv->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals('code', $errors[0]['field']);
    }

    /**
     * Tue mutant #9 (ConditionalEngine ligne 193)
     * Test que reset() remet bien thenMode à false
     */
    public function testResetClearsThenMode(): void
    {
        $data = new stdClass();
        $data->type = 'premium';
        $data->field1 = 'value';
        $data->field2 = 'value';

        $dv = new DataVerify($data);
        
        // Premier champ avec condition
        $dv->field('field1')
            ->when('type', '=', 'premium')
            ->then->required;
        
        // Le reset entre les champs doit permettre validation normale
        $dv->field('field2')->required;
        
        // Si ça passe sans throw, reset a bien fonctionné
        $this->assertTrue($dv->verify());
    }

    /**
     * Test AND condition - tous doivent être vrais
     */
    public function testAndConditionAllMustBeTrue(): void
    {
        // Cas 1: Premier false
        $data1 = new stdClass();
        $data1->type = 'basic'; // false
        $data1->amount = 150;   // true
        $data1->code = '';

        $dv1 = new DataVerify($data1);
        $dv1->field('code')
            ->when('type', '=', 'premium')
            ->and('amount', '>', 100)
            ->then->required;

        $this->assertTrue($dv1->verify()); // Skip car AND false

        // Cas 2: Deuxième false
        $data2 = new stdClass();
        $data2->type = 'premium'; // true
        $data2->amount = 50;      // false
        $data2->code = '';

        $dv2 = new DataVerify($data2);
        $dv2->field('code')
            ->when('type', '=', 'premium')
            ->and('amount', '>', 100)
            ->then->required;

        $this->assertTrue($dv2->verify()); // Skip car AND false

        // Cas 3: Les deux true
        $data3 = new stdClass();
        $data3->type = 'premium'; // true
        $data3->amount = 150;     // true
        $data3->code = '';

        $dv3 = new DataVerify($data3);
        $dv3->field('code')
            ->when('type', '=', 'premium')
            ->and('amount', '>', 100)
            ->then->required;

        $this->assertFalse($dv3->verify()); // Execute car AND true
    }

    /**
     * Test OR condition - au moins un doit être vrai
     */
    public function testOrConditionAtLeastOneMustBeTrue(): void
    {
        // Cas 1: Premier true
        $data1 = new stdClass();
        $data1->country = 'FR';  // true
        $data1->region = 'Asia'; // false
        $data1->vat = '';

        $dv1 = new DataVerify($data1);
        $dv1->field('vat')
            ->when('country', '=', 'FR')
            ->or('region', '=', 'EU')
            ->then->required;

        $this->assertFalse($dv1->verify()); // Execute car OR true

        // Cas 2: Deuxième true
        $data2 = new stdClass();
        $data2->country = 'DE';  // false (!=FR)
        $data2->region = 'EU';   // true
        $data2->vat = '';

        $dv2 = new DataVerify($data2);
        $dv2->field('vat')
            ->when('country', '=', 'FR')
            ->or('region', '=', 'EU')
            ->then->required;

        $this->assertFalse($dv2->verify()); // Execute car OR true

        // Cas 3: Les deux false
        $data3 = new stdClass();
        $data3->country = 'US';   // false
        $data3->region = 'Asia';  // false
        $data3->vat = '';

        $dv3 = new DataVerify($data3);
        $dv3->field('vat')
            ->when('country', '=', 'FR')
            ->or('region', '=', 'EU')
            ->then->required;

        $this->assertTrue($dv3->verify()); // Skip car OR false
    }

    public function testWhenWithoutThenThrowsLogicException(): void
    {
        $data = ['field' => 'value', 'trigger' => 'active'];
        $dv = new DataVerify($data);
        
        $dv->field('field')->when('trigger', '=', 'active');
        
        // Tenter d'ajouter une validation sans 'then'
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Incomplete conditional validation");
        $dv->field('field')->string;
    }

    public function testConditionalValidationOnlyAppliesWhenThenModeActive(): void
    {
        $data = ['field' => 'invalid', 'trigger' => 'inactive'];
        $dv = new DataVerify($data);
        
        // Condition non remplie, validation pas appliquée
        $dv->field('field')
            ->when('trigger', '=', 'active')
            ->then->email;
        
        $this->assertTrue($dv->verify()); // Pas d'erreur car condition false
    }
}