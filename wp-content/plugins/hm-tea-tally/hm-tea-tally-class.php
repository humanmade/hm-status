<?php 

/**
 * HMOT_User class.
 */
class HM_Tea_Tally {

	/**
	 * __construct function.
	 * 
	 * @access public
	 * @param mixed $id
	 * @param bool $employment_start (default: false)
	 * @param bool $holidays_offset (default: false)
	 * @param bool $holidays_per_year (default: false)
	 * @return void
	 */
	function __construct( ){
	
		$this->grab_users();
	
	}
	
	/**
	 * grab_userdata function.
	 * 
	 * @access public
	 * @return array
	 */
	function grab_users() {
	
		$users = get_users( array(
		
			'meta_key' => 'hmtt_active',
			'meta_compare' => '>',
			'meta_value' => 0,
	
		) ); 
		
		foreach ( $users as $key => $user ){
			
			$this->users[$user->ID] = $user;
			
			$this->users[$user->ID]->name					= ( $name = get_the_author_meta( 'first_name', $user->ID ) ) ? $name : $this->users[$user->ID]->display_name; 		
			$this->users[$user->ID]->hmtt_offset 			= (int) get_user_meta( $user->ID, 'hmtt_offset', true );
			$this->users[$user->ID]->hmtt_rolling_total  	= (int) get_user_meta( $user->ID, 'hmtt_rolling_total', true );
			$this->users[$user->ID]->hmtt_total 			= $this->users[$user->ID]->hmtt_rolling_total + (int) get_user_meta( $user->ID, 'hmtt_offset', true );
		}
		
		if ( isset( $this->users ) )	
			return $this->users;
		
		return false;		
	}			

	/**
	 * set_wage function.
	 * 
	 * @access public
	 * @param int $date (default: 0)
	 * @return void
	 */
	function set_offset( $offset = 0 ) {

		if ( ! $offset )
			return;
	
		update_user_meta( $this->ID, 'hmtt_offset', $offset );
	}
	
	/**
	 * add_tea function.
	 * 
	 * @access public
	 * @return int
	 */
	function add_tea( $user_id ) {
		
		update_user_meta( $user_id, 'hmtt_rolling_total', (int) get_user_meta( $user_id, 'hmtt_rolling_total', true ) + 1 );
		
		$this->users[$user_id]->hmtt_rolling_total++;
		$this->users[$user_id]->hmtt_total++;
		
		return;
	}	
	
	/**
	 * remove_tea function.
	 * 
	 * @access public
	 * @return int
	 */
	function remove_tea( $user_id ) {
		
		update_user_meta( $user_id, 'hmtt_rolling_total', ( (int) get_user_meta( $user_id, 'hmtt_rolling_total', true ) - 1 ) );
		
		$this->users[$user_id]->hmtt_rolling_total--;
		$this->users[$user_id]->hmtt_total--;		
		return;
	}	

	/**
	 * do_a_round function.
	 * 
	 * @access public
	 * @param mixed $start_date
	 * @param mixed $duration
	 * @return int
	 */
	function do_a_round ( $who_made = 0, $who_received = array()  ) {
		 
		 $data = $this->users[$who_made]->name . ' made tea for: <br />';	
		 
		 foreach ( $who_received as $key => $person ){
			
			if ( $who_made == $person ) {
				
				unset( $who_received[$key] );
				
				continue;
			}
				
		 	$data .= $this->users[$person]->name . ' (' . $this->users[$person]->hmtt_total . ') <br />';
		 	
		 	$this->add_tea( $person );
		 	
		 	$this->remove_tea( $who_made );
		 }
		
		$people = ( count( $who_received ) > 1 ) ? count ( $who_received ) . ' people' : count ( $who_received ) . ' person';
		
		$post = array(
		  'post_author' => $who_made,
		  'post_content' => $data,
		  'post_name' => sanitize_title( $this->users[$who_made]->name . ' made a round for ' . $people ),
		  'post_title' => $this->users[$who_made]->name . ' made a round for '  . $people,
		  'post_type' => 'tea-round',
		  'post_status' => 'publish'
		);  
		
		$post_id = wp_insert_post( $post );
		
		update_post_meta( $post_id, 'hmtt_who_received', $who_received );
		update_post_meta( $post_id, 'hmtt_who_made', $who_made );
		update_post_meta( $post_id, 'hmtt_who_received_count', count ( $who_received ) );
		update_post_meta( $post_id, 'hmtt_date', time() );
		
		return $post_id;	
	}

}