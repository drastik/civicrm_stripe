<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * FIXME - Add test description.
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test class.
 *    Simply create corresponding functions (e.g. "hook_civicrm_post(...)" or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or test****() functions will
 *    rollback automatically -- as long as you don't manipulate schema or truncate tables.
 *    If this test needs to manipulate schema or truncate tables, then either:
 *       a. Do all that using setupHeadless() and Civi\Test.
 *       b. Disable TransactionalInterface, and handle all setup/teardown yourself.
 *
 * @group headless
 */
class CRM_Stripe_BaseTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  protected $_contributionID;
  protected $_invoiceID = 'in_19WvbKAwDouDdbFCkOnSwAN7';
  protected $_financialTypeID = 1;
  protected $_contactID;
  protected $_contributionPageID;
  protected $_paymentProcessorID;
  protected $_paymentProcessor;
	protected $_created_ts;
	// Secret/public keys are PTP test keys.
	// protected $_sk = 'sk_test_TlGdeoi8e1EOPC3nvcJ4q5UZ';
	// protected $_pk = 'pk_test_k2hELLGpBLsOJr6jZ2z9RaYh';
  // MFPL Secret key
  protected $_sk = 'sk_test_0f3Nja19AQvQvLczwI5lV021';
  protected $_pk = 'pk_test_4Q4VmBJAjn93vENmkka8YWSD';

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp() {
    parent::setUp();
 		$this->createPaymentProcessor();
 		$this->createContact();
    $this->createContributionPage();
		$this->_created_ts = time();
  }

  public function tearDown() {
    parent::tearDown();
  }

  /**
   * Create contact.
   */
  function createContact() {
    $this->contact =   $this->contact = \CRM_Core_DAO::createTestObject(
      'CRM_Contact_DAO_Contact', 
      array('contact_type' => 'Individual',)
    );
    $this->_contactID = $this->contact->id;
    $this->contact = $this->contact;

    // Now we have to add an email address.
    $email = 'susie@example.org';
    civicrm_api3('email', 'create', array(
      'contact_id' => $this->_contactID,
      'email' => $email,
      'location_type_id' => 1
    ));
    $this->contact->email = $email;


  }

	/**
   * Create a stripe payment processor.
   *
   */
  function createPaymentProcessor($params = array()) {
    $params = array_merge(array(
        'name' => 'Stripe',
        'domain_id' => CRM_Core_Config::domainID(),
 		    'payment_processor_type_id' => 'Stripe',
		    'title' => 'Stripe',
        'is_active' => 1,
        'is_default' => 0,
        'is_test' => 1,
        'is_recur' => 1,
		    'user_name' => $this->_sk,
		    'password' => $this->_pk,
		    'url_site' => 'https://api.stripe.com/v1',
		    'url_recur' => 'https://api.stripe.com/v1',
        'class_name' => 'Payment_Stripe',
        'billing_mode' => 1
      ), $params);
    $result = civicrm_api3('PaymentProcessor', 'create', $params);
		$this->assertEquals(0, $result['is_error']);
    $this->_paymentProcessor = array_pop($result['values']); 
    $this->_paymentProcessorID = $result['id']; 
  }
  /**
   * Create a stripe contribution page.
   *
   */
  function createContributionPage($params = array()) {
    $params = array_merge(array(
      'title' => "Test Contribution Page",
      'financial_type_id' => $this->_financialTypeID,
      'currency' => 'USD',
      'payment_processor' => $this->_paymentProcessorID,
      'max_amount' => 1000,
      'receipt_from_email' => 'gaia@the.cosmos',
      'receipt_from_name' => 'Pachamama',
      'is_email_receipt' => TRUE,  
      ), $params);
    $result = civicrm_api3('ContributionPage', 'create', $params);
		$this->assertEquals(0, $result['is_error']);
    $this->_contributionPageID = $result['id']; 
  }
  
  
}
