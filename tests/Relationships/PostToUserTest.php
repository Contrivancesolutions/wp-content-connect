<?php

namespace TenUp\P2P\Tests\Relationships;

use TenUp\P2P\Relationships\PostToUser;
use TenUp\P2P\Tests\P2PTestCase;

class PostToUserTest extends P2PTestCase {

	public function setUp() {
		global $wpdb;

		$wpdb->query( "delete from {$wpdb->prefix}post_to_user" );

		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function test_invalid_cpt_throws_exception() {
		$this->expectException( \Exception::class );

		new PostToUser( 'fakecpt', 'basic' );
	}

	public function test_valid_cpts_throw_no_exceptions() {
		$p2p = new PostToUser( 'post', 'basic' );

		$this->assertEquals( 'post', $p2p->post_type );
	}

	public function test_add_relationship() {
		global $wpdb;
		$p2u = new PostToUser( 'post', 'basic' );

		// Make sure we don't already have this in the DB
		$this->assertEquals( 0, $wpdb->query( "select * from {$wpdb->prefix}post_to_user where post_id='2' and user_id='1' and type='basic'") );

		$p2u->add_relationship( 2, 1 );
		$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_user where post_id='2' and user_id='1' and type='basic'") );
	}

	public function test_adding_duplicates() {
		global $wpdb;
		$p2u = new PostToUser( 'post', 'basic' );

		// Making sure we don't add duplicates
		$p2u->add_relationship( '2', '1' );
		$p2u->add_relationship( '2', '1' );
		$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_user where post_id='2' and user_id='1' and type='basic'") );
	}

	public function test_delete_relationship() {
		global $wpdb;
		$p2u = new PostToUser( 'post', 'basic' );

		// Make sure we're in a known state of having a relationship in the DB
		$p2u->add_relationship( '2', '1' );
		$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_user where post_id='2' and user_id='1' and type='basic'") );

		$p2u->delete_relationship( 2, 1 );
		$this->assertEquals( 0, $wpdb->query( "select * from {$wpdb->prefix}post_to_user where post_id='2' and user_id='1' and type='basic'") );
	}

	public function test_delete_only_deletes_correct_records() {
		global $wpdb;
		$p2u = new PostToUser( 'post', 'basic' );

		$keep_pairs = array(
			array( 2, 2 ),
			array( 2, 5 ),
			array( 3, 10 ),
			array( 4, 15 ),
		);

		$delete_pairs = array(
			array( 2, 10 ),
		);

		$pairs = array_merge( $keep_pairs, $delete_pairs );

		foreach ( $pairs as $pair ) {
			$p2u->add_relationship( $pair[0], $pair[1] );
		}

		foreach( $pairs as $pair ) {
			$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_user where post_id='{$pair[0]}' and user_id='{$pair[1]}' and type='basic'") );
		}

		foreach( $delete_pairs as $delete_pair ) {
			$p2u->delete_relationship( $pair[0], $pair[1] );
		}

		foreach( $keep_pairs as $pair ) {
			$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_user where post_id='{$pair[0]}' and user_id='{$pair[1]}' and type='basic'") );
		}

		foreach( $delete_pairs as $pair ) {
			$this->assertEquals( 0, $wpdb->query( "select * from {$wpdb->prefix}post_to_user where post_id='{$pair[0]}' and user_id='{$pair[1]}' and type='basic'") );
		}
	}

	public function test_user_ids_from_posts() {
		$this->add_user_relations();

		$postowner = new PostToUser( 'post', 'owner' );
		$postcontrib = new PostToUser( 'post', 'contrib' );
		$carowner = new PostToUser( 'car', 'owner' );
		$carcontrib = new PostToUser( 'car', 'contrib' );

		$this->assertEquals( array( 1 ), $postowner->get_related_user_ids( 1 ) );
		$this->assertEquals( array( 1 ), $postowner->get_related_user_ids( 2 ) );
		$this->assertEquals( array( 1, 2 ), $postowner->get_related_user_ids( 3 ) );
		$this->assertEquals( array( 1, 2 ), $postowner->get_related_user_ids( 4 ) );
		$this->assertEquals( array( 1, 2, 3 ), $postowner->get_related_user_ids( 5 ) );
		$this->assertEquals( array( 3 ), $postowner->get_related_user_ids( 8 ) );
		$this->assertEquals( array(), $postowner->get_related_user_ids( 10 ) );

		// Because 12 is not a 'post' post type, this should return no results, but we aren't restricting on the `FROM` side right now
		$this->assertEquals( array(), $postowner->get_related_user_ids( 12 ) );

		$this->assertEquals( array(), $postcontrib->get_related_user_ids( 1 ) );
		$this->assertEquals( array( 1 ), $postcontrib->get_related_user_ids( 2 ) );
		$this->assertEquals( array( 1 ), $postcontrib->get_related_user_ids( 3 ) );
		$this->assertEquals( array( 1, 2 ), $postcontrib->get_related_user_ids( 4 ) );

		$this->assertEquals( array(), $carowner->get_related_user_ids( 11 ) );
		$this->assertEquals( array( 3 ), $carowner->get_related_user_ids( 12 ) );
		$this->assertEquals( array( 3 ), $carowner->get_related_user_ids( 13 ) );
		$this->assertEquals( array( 2, 3 ), $carowner->get_related_user_ids( 14 ) );

		$this->assertEquals( array( 3 ), $carcontrib->get_related_user_ids( 11 ) );
		$this->assertEquals( array( 3 ), $carcontrib->get_related_user_ids( 12 ) );
		$this->assertEquals( array( 2, 3 ), $carcontrib->get_related_user_ids( 13 ) );
		$this->assertEquals( array(), $carcontrib->get_related_user_ids( 20 ) );

	}

	public function test_post_ids_from_users() {
		$this->add_user_relations();

		$postowner = new PostToUser( 'post', 'owner' );
		$postcontrib = new PostToUser( 'post', 'contrib' );
		$carowner = new PostToUser( 'car', 'owner' );
		$carcontrib = new PostToUser( 'car', 'contrib' );

		$this->assertEquals( array( 1, 2, 3, 4, 5 ), $postowner->get_related_post_ids( 1 ) );
		$this->assertEquals( array( 16, 17, 18, 19, 20 ), $carowner->get_related_post_ids( 1 ) );
		$this->assertEquals( array( 2, 3, 4, 5, 6 ), $postcontrib->get_related_post_ids( 1 ) );
		$this->assertEquals( array( 15, 16, 17, 18, 19 ), $carcontrib->get_related_post_ids( 1 ) );

		$this->assertEquals( array( 3, 4, 5, 6, 7 ), $postowner->get_related_post_ids( 2 ) );
		$this->assertEquals( array( 14, 15, 16, 17, 18 ), $carowner->get_related_post_ids( 2 ) );
		$this->assertEquals( array( 4, 5, 6, 7, 8 ), $postcontrib->get_related_post_ids( 2 ) );
		$this->assertEquals( array( 13, 14, 15, 16, 17 ), $carcontrib->get_related_post_ids( 2 ) );

		$this->assertEquals( array( 5, 6, 7, 8, 9 ), $postowner->get_related_post_ids( 3 ) );
		$this->assertEquals( array( 12, 13, 14, 15, 16 ), $carowner->get_related_post_ids( 3 ) );
		$this->assertEquals( array( 6, 7, 8, 9, 10 ), $postcontrib->get_related_post_ids( 3 ) );
		$this->assertEquals( array( 11, 12, 13, 14, 15 ), $carcontrib->get_related_post_ids( 3 ) );
	}

}