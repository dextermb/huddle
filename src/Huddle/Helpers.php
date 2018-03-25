<?php
if (!function_exists('huddle')) {
	function huddle($item = [])
	{
		$items = func_get_args();

		if (is_array($items[0]) && isset($items[0][0])) {
			$items = $items[0];
		}

		return new \Huddle\Huddle($items);
	}
}