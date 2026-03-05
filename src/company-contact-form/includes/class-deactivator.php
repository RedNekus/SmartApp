<?php
namespace CCF;

class Deactivator {
	public static function deactivate() {
		wp_clear_scheduled_hook( 'ccf_rotate_logs' );
		flush_rewrite_rules();
	}
}
