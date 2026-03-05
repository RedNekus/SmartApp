<?php
/**
 * Integration tests using WP_UnitTestCase
 *
 * @package Company Contact Form
 * @group integration
 */

class CCF_Test_Integration extends WP_UnitTestCase {

    public function set_up() {
        parent::set_up();
        $this->admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
    }

    /**
     * Test email validation (RFC-compliant)
     * 
     * Note: is_email() returns sanitized email string on success, false on failure
     * 
     * @dataProvider email_provider
     */
    public function test_email_validation( $email, $should_be_valid ) {
        $result = is_email( $email );
        
        if ( $should_be_valid ) {
            // Valid emails: assertNotFalse (returns sanitized email string)
            $this->assertNotFalse( $result, "Valid email should not return false: $email" );
            $this->assertIsString( $result, "Valid email should return string: $email" );
        } else {
            // Invalid emails: assertFalse
            $this->assertFalse( $result, "Invalid email should return false: $email" );
        }
    }

    public function email_provider() {
        return [
            'valid_simple'      => [ 'user@example.com', true ],
            'valid_plus_tag'    => [ 'user+tag@sub.domain.co.uk', true ],
            'valid_dash'        => [ 'first-last@company.org', true ],
            'invalid_no_at'     => [ 'userexample.com', false ],
            'invalid_no_domain' => [ 'user@', false ],
            'invalid_empty'     => [ '', false ],
        ];
    }

    /**
     * Test nonce verification
     * 
     * Note: wp_verify_nonce() returns 1 or 2 on success, false on failure
     */
    public function test_nonce_verification() {
        wp_set_current_user( $this->admin_id );
        
        $action = 'wp_rest';
        $nonce = wp_create_nonce( $action );
        
        // Valid nonce should verify (returns 1 or 2, not true)
        $this->assertNotFalse( wp_verify_nonce( $nonce, $action ), 'Valid nonce should verify' );
        
        // Invalid nonce should fail
        $this->assertFalse( wp_verify_nonce( 'invalid-nonce', $action ), 'Invalid nonce should fail' );
        
        // Wrong action should fail
        $this->assertFalse( wp_verify_nonce( $nonce, 'wrong-action' ), 'Wrong action should fail' );
    }

    /**
     * Test rate limit logic using transients
     */
    public function test_rate_limit_logic() {
        $key = 'ccf_test_rate_limit';
        $limit = 3;
        $window = 60;
        
        delete_transient( $key );
        
        // Simulate requests up to limit
        for ( $i = 1; $i <= $limit; $i++ ) {
            $current = get_transient( $key ) ?: 0;
            set_transient( $key, $current + 1, $window );
        }
        
        // Check counter reached limit
        $this->assertEquals( $limit, get_transient( $key ), 'Counter should reach limit' );
        
        // Next check should indicate limit reached
        $current = get_transient( $key );
        $this->assertTrue( $current >= $limit, 'Rate limit should be triggered' );
    }

    /**
     * Test: Logger class methods exist
     */
    public function test_logger_class_structure() {
        $this->assertTrue( class_exists( 'CCF\\Logger' ), 'CCF\Logger class should exist' );
        
        if ( class_exists( 'CCF\\Logger' ) ) {
            $this->assertTrue( method_exists( 'CCF\\Logger', 'log' ), 'Logger::log() should exist' );
            $this->assertTrue( method_exists( 'CCF\\Logger', 'rotate_logs' ), 'Logger::rotate_logs() should exist' );
        }
    }

    /**
     * Test: Plugin constants are defined
     */
    public function test_plugin_constants() {
        $this->assertTrue( defined( 'CCF_VERSION' ), 'CCF_VERSION should be defined' );
        $this->assertTrue( defined( 'CCF_PATH' ), 'CCF_PATH should be defined' );
        $this->assertNotEmpty( CCF_VERSION, 'CCF_VERSION should not be empty' );
    }
}
