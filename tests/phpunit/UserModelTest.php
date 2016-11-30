<?php
/**
 * User entity test cases
 *
 * @category   UnitTests
 * @package    tests
 * @copyright  Copyright (c) 2016, Arroyo Labs, http://www.arroyolabs.com
 *
 * @author     John Arroyo, john@arroyolabs.com
 * @author     Leo Daidone, leo@arroyolabs.com
 */
namespace tests\phpunit;

require_once dirname(__DIR__).'/ErdikoTestCase.php';


class UserModelTest extends \tests\ErdikoTestCase
{
	protected $entityManager = null;
	protected $userArrayData;
	protected $userArrayUpdate;
	protected $model;
	protected static $lastID;

	function setUp()
	{
		$this->entityManager = \erdiko\doctrine\EntityManager::getEntityManager();
		$this->userArrayData = array(
			"email"=>"leo@testlabs.com",
			"password"=>"asdf1234",
			"role"=>"super-admin",
			"name"=>"Test",
		);
		$this->userArrayUpdate = array(
			"id"=>null,
			"email"=>"leo@arroyolabs.com",
			"password"=>"asdf1234",
			"role"=>"admin",
			"name"=>"Test 2",
		);
		$this->model = new \erdiko\users\models\User();
	}

	/**
	 * @expectedException TypeError
	 */
	public function testSetEntityFail()
	{
		try {
			$obj   = (object) array();
			$this->model->setEntity( $obj );
		} catch (\Exception $e) {}
	}

	public function testSetEntity()
	{
		$entity = new \erdiko\users\entities\User();
		$entity->setId( 0 );
		$entity->setRole( 'anonymous' );
		$entity->setName( 'anonymous' );
		$entity->setEmail( 'anonymous' );
		$this->model->setEntity($entity);
	}

	/**
	 *
	 */
	public function testGetEntity()
	{
		$entity = $this->model->getEntity();

		$this->assertInstanceOf('\erdiko\users\entities\User', $entity);
		$this->assertEquals('anonymous', $entity->getName());
		$this->assertEquals('anonymous', $entity->getRole());
		$this->assertEquals('anonymous', $entity->getEmail());
	}

	/**
	 *
	 */
	public function testMarshall()
	{
		$encoded = $this->model->marshall();
		$this->assertInternalType('string', $encoded);

		$out = (object)array(
			"id" => 0,
			"name" => 'anonymous',
			"role" => 'anonymous',
			"email" => 'anonymous',
			'gateway_customer_id' => null,
			'last_login' => null
		);

		$this->assertEquals($out, json_decode($encoded));
	}

	/**
	 *
	 */
	public function testUnmarshall()
	{
		$object = (object)array(
			"id" => 0,
			"name" => 'anonymous',
			"role" => 'anonymous',
			"email" => 'anonymous',
			'gateway_customer_id' => null,
			'last_login' => null
		);
		$out = $this->model->unmarshall(json_encode($object));

		$this->assertInstanceOf('\erdiko\users\models\User', $out);
		$this->assertNotEmpty($this->model->getEntity());
	}

	public function testGetSalted()
	{
		$password = "asdf1234";
		$salted = $this->model->getSalted($password);
		$expect = $password . \erdiko\users\models\User::PASSWORDSALT;
		$this->assertEquals($expect, $salted);
	}

	// CRUD related
	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage User data is missing
	 */
	public function testCreateUserNoData()
	{
		$this->model->createUser();
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage email & password are required
	 */
	public function testCreateUserFail()
	{
		$data = $this->userArrayData;
		unset($data['email'], $data['password']);
		$this->model->createUser($data);
	}

	/**
	 *
	 */
	public function testCreateUser()
	{
		$data = $this->userArrayData;
		$result = $this->model->createUser($data);

		$this->assertTrue($result);

		$newEntity = $this->model->getEntity();
		$this->userArrayUpdate['id'] = $newEntity->getId();
		self::$lastID = $newEntity->getId();
	}

	public function testIsAnonymous()
	{
		$result = $this->model->isAnonymous();
		$this->assertTrue($result);
	}

	/**
	 *
	 */
	public function testAuthenticateInvalid()
	{
		$logged = $this->model->isLoggedIn();
		$this->assertFalse($logged);

		$result = $this->model->authenticate( null, null );
		$this->assertFalse( $result );
	}

	/**
	 *
	 */
	public function testAuthenticate()
	{
		$email = $this->userArrayData['email'];
		$password = $this->userArrayData['password'];

		$result = $this->model->authenticate($email, $password);

		$this->assertNotEmpty($result);
		$this->assertInstanceOf('\erdiko\users\models\User', $result);

		// double check
		$logged = $this->model->isLoggedIn();
		$this->assertTrue($logged);

		$role = $this->model->hasRole('super-admin');
		$this->assertTrue($role);
	}

	public function testSave()
	{
		$params = $this->userArrayUpdate;
		$params['password'] = $this->model->getSalted($this->userArrayUpdate['password']);
		$params['id'] = self::$lastID;
		$result = $this->model->save($params);

		$this->assertInternalType('int',$result);
		$this->assertTrue(($result > 0));

		$entity = $this->model->getEntity();
		$this->assertEquals($entity->getEmail(),$this->userArrayUpdate['email']);
		$this->assertEquals($entity->getName(),$this->userArrayUpdate['name']);
		$this->assertEquals($entity->getRole(),$this->userArrayUpdate['role']);

		$admin = $this->model->isAdmin();
		$this->assertTrue($admin);

		$newEntity = $this->model->getEntity();
		$this->userArrayUpdate['id'] = $newEntity->getId();
		self::$lastID = $newEntity->getId();
	}

	public function testDelete()
	{
		$id = empty($this->userArrayUpdate['id']) ? self::$lastID : $this->userArrayUpdate['id'];
		$result = $this->model->deleteUser($id);

		$this->assertTrue($result);
	}


	function tearDown()
	{
		unset($this->entityManager);
	}
}