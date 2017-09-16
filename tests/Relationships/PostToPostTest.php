<?php

namespace TenUp\P2P\Tests\Relationships;

use TenUp\P2P\Relationships\PostToPost;
use TenUp\P2P\Tests\P2PTestCase;

class PostToPostTest extends P2PTestCase {

	public function setUp() {
		global $wpdb;

		$wpdb->query( "delete from {$wpdb->prefix}post_to_post" );

		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function test_invalid_cpt_throws_exception() {
		$this->expectException( \Exception::class );

		new PostToPost( 'post', 'fakecpt', 'basic' );
	}

	public function test_valid_cpts_throw_no_exceptions() {
		$p2p = new PostToPost( 'post', 'post', 'basic' );

		$this->assertEquals( 'post', $p2p->from );
		$this->assertEquals( 'post', $p2p->to );
	}

	public function test_add_relationship() {
		global $wpdb;
		$p2p = new PostToPost( 'post', 'post', 'basic' );

		// Make sure we don't already have this in the DB
		$this->assertEquals( 0, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='1' and id2='2' and type='basic'") );
		$this->assertEquals( 0, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='2' and id2='1' and type='basic'") );

		$p2p->add_relationship( '1', '2' );
		$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='1' and id2='2' and type='basic'") );
		$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='2' and id2='1' and type='basic'") );
	}

	public function test_adding_duplicates() {
		global $wpdb;
		$p2p = new PostToPost( 'post', 'post', 'basic' );

		// Making sure we don't add duplicates
		$p2p->add_relationship( '1', '2' );
		$p2p->add_relationship( '1', '2' );
		$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='1' and id2='2' and type='basic'") );
		$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='2' and id2='1' and type='basic'") );

		// Making sure that order doesn't matter / duplicates
		$p2p->add_relationship( 2, 1 );

		$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='1' and id2='2' and type='basic'") );
		$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='2' and id2='1' and type='basic'") );
	}

	public function test_delete_relationship() {
		global $wpdb;
		$p2p = new PostToPost( 'post', 'post', 'basic' );

		// Make sure we're in a known state of having a relationship in the DB
		$p2p->add_relationship( '1', '2' );
		$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='1' and id2='2' and type='basic'") );
		$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='2' and id2='1' and type='basic'") );

		$p2p->delete_relationship( 1, 2 );
		$this->assertEquals( 0, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='1' and id2='2' and type='basic'") );
		$this->assertEquals( 0, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='2' and id2='1' and type='basic'") );
	}

	public function test_delete_flipped_order() {
		global $wpdb;
		$p2p = new PostToPost( 'post', 'post', 'basic' );

		// Make sure we're in a known state of having a relationship in the DB
		$p2p->add_relationship( '1', '2' );
		$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='1' and id2='2' and type='basic'") );
		$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='2' and id2='1' and type='basic'") );

		$p2p->delete_relationship( 2, 1 );
		$this->assertEquals( 0, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='1' and id2='2' and type='basic'") );
		$this->assertEquals( 0, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='2' and id2='1' and type='basic'") );
	}

	public function test_delete_only_deletes_correct_records() {
		global $wpdb;
		$pp = new PostToPost( 'post', 'post', 'basic' );

		$keep_pairs = array(
			array( 1, 2 ),
			array( 1, 5 ),
			array( 2, 10 ),
			array( 2, 15 ),
		);

		$delete_pairs = array(
			array( 1, 10 ),
		);

		$pairs = array_merge( $keep_pairs, $delete_pairs );

		foreach ( $pairs as $pair ) {
			$pp->add_relationship( $pair[0], $pair[1] );
		}

		foreach( $pairs as $pair ) {
			$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='{$pair[0]}' and id2='{$pair[1]}' and type='basic'") );
			$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='{$pair[1]}' and id2='{$pair[0]}' and type='basic'") );
		}

		$pp->delete_relationship( 1, 10 );

		foreach( $keep_pairs as $pair ) {
			$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='{$pair[0]}' and id2='{$pair[1]}' and type='basic'") );
			$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='{$pair[1]}' and id2='{$pair[0]}' and type='basic'") );
		}

		foreach( $delete_pairs as $pair ) {
			$this->assertEquals( 0, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='{$pair[0]}' and id2='{$pair[1]}' and type='basic'") );
			$this->assertEquals( 0, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='{$pair[1]}' and id2='{$pair[0]}' and type='basic'") );
		}
	}

	public function test_that_posts_relate_to_posts() {
		$this->add_post_relations();

		$ppb = new PostToPost( 'post', 'post', 'basic' );
		$ppc = new PostToPost( 'post', 'post', 'complex' );

		$this->assertEquals( array( 2, 3 ), $ppb->get_related_object_ids( 1 ) );
		$this->assertEquals( array( 3, 4 ), $ppc->get_related_object_ids( 1 ) );
		$this->assertEquals( array( 1 ), $ppb->get_related_object_ids( 2 ) );
		$this->assertEquals( array( 1 ), $ppb->get_related_object_ids( 3 ) );
		$this->assertEquals( array( 1 ), $ppc->get_related_object_ids( 3 ) );
		$this->assertEquals( array( 1 ), $ppc->get_related_object_ids( 4 ) );
	}

	public function test_that_posts_relate_to_cars() {
		$this->add_post_relations();

		$pcb = new PostToPost( 'post', 'car', 'basic' );
		$pcc = new PostToPost( 'post', 'car', 'complex' );

		$this->assertEquals( array( 11, 12 ), $pcb->get_related_object_ids( 1 ) );
		$this->assertEquals( array( 13, 14 ), $pcc->get_related_object_ids( 1 ) );
		$this->assertEquals( array( 1 ), $pcb->get_related_object_ids( 11 ) );
		$this->assertEquals( array( 1 ), $pcb->get_related_object_ids( 12 ) );
		$this->assertEquals( array( 1 ), $pcc->get_related_object_ids( 13 ) );
		$this->assertEquals( array( 1 ), $pcc->get_related_object_ids( 14 ) );
	}

	public function test_that_posts_relate_to_tires() {
		$this->add_post_relations();

		$ptb = new PostToPost( 'post', 'tire', 'basic' );
		$ptc = new PostToPost( 'post', 'tire', 'complex' );

		$this->assertEquals( array( 21, 22 ), $ptb->get_related_object_ids( 1 ) );
		$this->assertEquals( array( 23, 24 ), $ptc->get_related_object_ids( 1 ) );
		$this->assertEquals( array( 1 ), $ptb->get_related_object_ids( 21 ) );
		$this->assertEquals( array( 1 ), $ptb->get_related_object_ids( 22 ) );
		$this->assertEquals( array( 1 ), $ptc->get_related_object_ids( 23 ) );
		$this->assertEquals( array( 1 ), $ptc->get_related_object_ids( 24 ) );
	}

	public function test_that_cars_relate_to_tires() {
		$this->add_post_relations();

		$ctb = new PostToPost( 'car', 'tire', 'basic' );
		$ctc = new PostToPost( 'car', 'tire', 'complex' );

		// even though 11 is related to array( 1, 21 ) - that is wrong post type. When JUST these post types, its just array( 21 )
		$this->assertEquals( array( 21 ), $ctb->get_related_object_ids( 11 ) );
		$this->assertEquals( array( 23 ), $ctc->get_related_object_ids( 13 ) );
		$this->assertEquals( array( 11 ), $ctb->get_related_object_ids( 21 ) );
		$this->assertEquals( array( 13 ), $ctc->get_related_object_ids( 23 ) );
	}

}
