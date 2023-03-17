<?php

/*
 * Plugin Name: Meetup Autopilot
 * Plugin URI: https:/staude.net/
 * Description: Generate Posts from "The Events Calendar" Events
 * Version: 0.2
 * Author: Frank Neumann-Staude
 * Author URI: https://staude.net
 * Compatibility: WordPress 4.9.9
 * GitHub Plugin URI: https://github.com/stk/Meetup-Autopilot
 * GitHub Branch: diani
 *
 */


/*  Copyright 2017-2019  Frank Neumann-Staude  (email : frank@staude.net)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

if (!class_exists( 'meetup_autopilot' ) ) {

    class meetup_autopilot {

        function __construct() {
            add_action( 'meetup_autopilot_event_post', array( $this, 'create_event_post' ) );
            add_action( 'meetup_autopilot_recap_post', array( $this, 'create_recap_post' ) );
        }

        /**
         *
         */
        function create_event_post() {
            setlocale(LC_TIME, "de_DE");
            $date = strtotime('+14 days', time() );  // 2 Wochen vor dem Termin ankündigen
            $args = array(
                'post_type'    => 'tribe_events',
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key'     => '_EventStartDate',
                        'value'   => date('Y-m-d', $date ) . ' 00:00:00',
                        'compare' => '<',
                    ),
                    array(
                        'relation' => 'OR',
                        array(
                            'key'     => '_autopilot_created_post',
                            'value'   => 1,
                            'compare' => '!=',
                        ),
                        array(
                            'key'     => '_autopilot_created_post',
                            'compare' => 'NOT EXISTS',
                        ),
                    ),
                )
            );
            $query = new WP_Query( $args );

            if ( $query->have_posts() ) {
                while ( $query->have_posts() ) {
                    // Post erzeugen
                    $query->the_post();
                    $event_id = get_the_ID();
                    $venue_id = get_metadata( 'post', $event_id, '_EventVenueID', true );
                    $venue_address = get_metadata( 'post', $venue_id, '_VenueAddress', true );
                    $venue_city = get_metadata( 'post', $venue_id, '_VenueCity', true );
                    $venue_zip = get_metadata( 'post', $venue_id, '_VenueZip', true );
                    $venue_name = get_the_title( $venue_id );
                    $event_name = get_the_title( $event_id );
                    $event_description = get_the_content();
                    $event_start = get_metadata( 'post', $event_id, '_EventStartDate', true );
                    $event_end = get_metadata( 'post', $event_id, '_EventEndDate', true );
                    $startts = strtotime($event_start );
                    $tag = strftime( '%A', $startts );
                    $datum = strftime( '%d. %B %G', $startts );
                    $start = strftime( '%H:%M', $startts );
                    $ende = '';
                    if ( $event_start == $event_end ) {
                        $endts = '';
                    } else {
                        $endts = strtotime( $event_end );
                        $ende = ' bis ' . strftime( '%H:%M', $endts );
                    }
                    $event_meetup_url = get_metadata( 'post', $event_id, '_EventURL', true );
                    $meetup_url = $event_meetup_url;

                    $text = <<<END


$event_name \n
\n
$tag, $datum\n
$start $ende Uhr\n
$venue_name\n
$venue_address\n
$venue_zip $venue_city\n
\n
\n
$event_description\n
\n
---
We're the WordPress Meetup in Diani Beach, Kenya (Community/User Group) and meet regularly every month on the -tba- day from -tba- time at the Coast-Working Diani Beach at Lotfa Resort, Diani Beach Rd/Mvindeni Rd.\n
\n
Our WordPress Meetup is open for everyone interested, no matter if user, developer, student or entrepreneur. Everyone is welcome, the level of knowledge is mixed and we are always happy to see new faces.\n
\n
Our meetings last usually about 1 hour, mostly about a specific topic, followed by an open discussion / exchange for all WordPress topics. But we also like to look outside the box.\n
\n
Kindly RSVP here: <a href="$meetup_url">$meetup_url</a>\n
\n
More info are to be found at: <a href="https://wpmeetup-diani.co.ke">https://wpmeetup-diani.co.ke</a> oder auf Twitter: https://twitter.com/wpmdiani\n
\n
END;

                    $my_post = array(
                        'post_title'    => $event_name,
                        'post_content'  => $text,
                        'post_status'   => 'publish',
                        'post_author'   => 1,
                        'post_category' => array( 4 ) // Dates
                    );

                    $gen_post = wp_insert_post( $my_post );
                    add_metadata( 'post', $event_id, '_autopilot_created_post', 1);

                }
            }

        }

        /**
         *
         */
        function create_recap_post() {

            setlocale(LC_TIME, "de_DE");
            $date = strtotime('+1 days', time() );
            $args = array(
                'post_type'    => 'tribe_events',
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key'     => '_EventStartDate',
                        'value'   => date('Y-m-d', $date ) . ' 00:00:00',
                        'compare' => '<',
                    ),
                    array(
                        'relation' => 'OR',
                        array(
                            'key'     => '_autopilot_created_recap',
                            'value'   => 1,
                            'compare' => '!=',
                        ),
                        array(
                            'key'     => '_autopilot_created_recap',
                            'compare' => 'NOT EXISTS',
                        ),
                    ),
                )
            );
            $query = new WP_Query( $args );

            if ( $query->have_posts() ) {
                while ( $query->have_posts() ) {
                    // Post erzeugen
                    $query->the_post();
                    $event_id = get_the_ID();
                    $venue_id = get_metadata( 'post', $event_id, '_EventVenueID', true );
                    $venue_address = get_metadata( 'post', $venue_id, '_VenueAddress', true );
                    $venue_city = get_metadata( 'post', $venue_id, '_VenueCity', true );
                    $venue_zip = get_metadata( 'post', $venue_id, '_VenueZip', true );
                    $venue_name = get_the_title( $venue_id );
                    $event_name = get_the_title( $event_id );
                    $event_description = get_the_content();
                    $event_start = get_metadata( 'post', $event_id, '_EventStartDate', true );
                    $event_end = get_metadata( 'post', $event_id, '_EventEndDate', true );
                    $startts = strtotime($event_start );
                    $tag = strftime( '%A', $startts );
                    $datum = strftime( '%d. %B %G', $startts );
                    $start = strftime( '%H:%M', $startts );
                    $ende = '';
                    if ( $event_start == $event_end ) {
                        $endts = '';
                    } else {
                        $endts = strtotime( $event_end );
                        $ende = ' bis ' . strftime( '%H:%M', $endts );
                    }
                    $event_meetup_url = get_metadata( 'post', $event_id, '_EventURL', true );
                    $meetup_url = $event_meetup_url;

                    $text = <<<END

At WordPress Meetup Diani ---speaker--- presented on $datum ---topic---.\n
\n
<blockquote>$event_description</blockquote>\n
\n
---\n
\n
---link to slideshare/wordpress.tv---.\n
\n
END;

                    $my_post = array(
                        'post_title'    => $event_name,
                        'post_content'  => $text,
                        'post_status'   => 'draft',
                        'post_author'   => 1,
                        'post_category' => array( 5 ) // Recap
                    );

                    $gen_post = wp_insert_post( $my_post );
                    add_metadata( 'post', $event_id, '_autopilot_created_recap', 1);

                }
            }


        }

    }

    $scheduled_meetup_autopilot = new meetup_autopilot;
    register_deactivation_hook( __FILE__, 'meetup_autopilot_deactivate' );

    function meetup_autopilot_deactivate() {
        $timestamp = wp_next_scheduled( 'meetup_autopilot_event_post' );
        wp_unschedule_event( $timestamp, 'meetup_autopilot_event_post' );
        $timestamp = wp_next_scheduled( 'meetup_autopilot_recap_post' );
        wp_unschedule_event( $timestamp, 'meetup_autopilot_recap_post' );
    }

    register_activation_hook( __FILE__, 'meetup_autopilot_activate' );
    function meetup_autopilot_activate() {

        if ( ! wp_next_scheduled( 'meetup_autopilot_event_post' ) ) {
            wp_schedule_event( time(), 'hourly', 'meetup_autopilot_event_post' );
        }
        if ( ! wp_next_scheduled( 'meetup_autopilot_recap_post' ) ) {
            wp_schedule_event( time(), 'hourly', 'meetup_autopilot_recap_post' );
        }

    }

}
