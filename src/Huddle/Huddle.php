<?php
namespace Huddle;


/**
 * Class Huddle
 *
 * @package Huddle
 */
class Huddle
{
	/** @var array $original */
	protected $original = [];

	/** @var array $altered */
	protected $altered = [];

	/**
	 * @param array $arr
	 */
	public function __construct(array $arr = [])
	{
		$this->original = $this->altered = $arr;
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->toJson();
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		return $this->altered;
	}

	/**
	 * @return string
	 */
	public function toJson()
	{
		return json_encode($this->altered);
	}

	/**
	 * @param array $arr
	 * @return $this
	 */
	public function set(array $arr = [])
	{
		$this->original = $this->altered = $arr;

		return $this;
	}

	/**
	 * @return mixed|null
	 */
	public function first()
	{
		if ($this->empty()) {
			return null;
		}

		if (isset($this->altered[0])) {
			return $this->altered[0];
		}

		$keys = array_keys($this->altered);

		return $this->altered[ $keys[0] ];
	}

	/**
	 * @return mixed|null
	 */
	public function last()
	{
		if ($this->empty()) {
			return null;
		}

		$count = count($this->altered);

		if (isset($this->altered[ $count - 1 ])) {
			return $this->altered[ $count - 1 ];
		}

		$keys = array_keys($this->altered);

		return $this->altered[ $keys[ $count - 1 ] ];
	}

	/**
	 * @param string|int $key
	 * @return mixed|array|null
	 */
	public function get($key = null)
	{
		if (is_null($key)) {
			return $this->all();
		}

		return isset($this->altered[ $key ]) ? $this->altered[ $key ] : null;
	}

	/**
	 * @return array
	 */
	public function all()
	{
		return $this->toArray();
	}

	/**
	 * @param bool $die
	 * @param bool $pre
	 * @return void
	 */
	public function dump(bool $die = true, bool $pre = true)
	{
		if ($pre) echo '<pre>';

		var_dump($this->toArray());

		if ($pre) echo '</pre>';
		if ($die) die;
	}

	/**
	 * @return bool
	 */
	public function empty()
	{
		return empty($this->altered);
	}

	/**
	 * @return int
	 */
	public function count()
	{
		return count($this->altered);
	}

	/**
	 * @return $this
	 */
	public function reset()
	{
		$this->altered = $this->original;

		return $this;
	}

	/**
	 * @param mixed $item
	 * @return $this
	 */
	public function push($item)
	{
		$this->altered = array_merge($this->altered, func_get_args());

		return $this;
	}

	/**
	 * @param string|int $key
	 * @param mixed      $item
	 * @return $this
	 */
	public function put($key, $item)
	{
		$this->altered[ $key ] = $item;

		return $this;
	}

	/**
	 * @param array|Huddle $arr
	 * @return $this
	 */
	public function merge($arr)
	{
		$merges = func_get_args();

		foreach ($merges as $merge) {
			if (!($merge instanceof Huddle)) {

				/** @var Huddle $merge */
				$this->altered = array_merge($this->altered, $merge->toArray());

				continue;
			}

			$this->altered = array_merge($this->altered, (array)$merge);
		}

		return $this;
	}

	/**
	 * @param int  $depth
	 * @param bool $preserve_keys
	 * @return $this
	 */
	public function flattern(int $depth = null, bool $preserve_keys = false)
	{
		$tmp = [];

		foreach ($this->altered as $key => $element) {
			if (is_array($element) && (is_null($depth) || !empty($depth))) {
				$tmp = array_merge(
					$tmp,
					(new self($element))->flattern(is_numeric($depth) ? --$depth : null)->toArray()
				);

				continue;
			}

			$tmp[ $key ] = $element;
		}

		$this->altered = $preserve_keys ? $tmp : array_values($tmp);

		return $this;
	}

	/**
	 * @param string|int $scope
	 * @param bool       $preserve_keys
	 * @return $this
	 */
	public function flatternUntil($scope, bool $preserve_keys = false)
	{
		$tmp = [];

		foreach ($this->altered as $key => $element) {
			if (is_array($element) && $key !== $scope) {
				$tmp = array_merge(
					$tmp,
					(new self($element))->flatternUntil($scope)->toArray()
				);

				continue;
			}

			$tmp[ $key ] = $element;
		}

		$this->altered = $preserve_keys ? $tmp : array_values($tmp);

		return $this;
	}

	/**
	 * @param callable $callable
	 * @return $this
	 */
	public function intercept(callable $callable)
	{
		call_user_func($callable, $this->altered, $this);

		return $this;
	}

	/**
	 * @param callable $callable
	 * @return $this
	 */
	public function each(callable $callable)
	{
		foreach ($this->altered as $key => $element) {
			call_user_func($callable, $key, $element);
		}

		return $this;
	}

	/**
	 * @param callable $callable
	 * @return $this
	 */
	public function filter(callable $callable)
	{
		$this->altered = array_filter($this->altered, $callable);

		return $this;
	}

	/**
	 * @param callable $callable
	 * @return $this
	 */
	public function map(callable $callable)
	{
		$this->altered = array_map($callable, $this->altered);

		return $this;
	}

	/**
	 * @param string|int $scope
	 * @param bool       $preserve_keys
	 * @param bool       $strict
	 * @return $this
	 */
	public function unique($scope = null, bool $preserve_keys = false, bool $strict = false)
	{
		if (is_null($scope)) {

			// https://stackoverflow.com/a/946300
			$this->altered = array_map(
				$callback = 'unserialize',
				array_unique(
					array_map(
						$callback = 'serialize',
						$this->altered
					)
				)
			);

			return $this;
		}

		$found    = [];
		$filtered = [];

		foreach ($this->altered as $key => $element) {
			if (is_array($element)) {
				if (isset($element[ $scope ])) {
					if (in_array($element[ $scope ], $found, $strict)) {
						continue;
					}

					$found[] = $element[ $scope ];
				}

				$this->uniqueHelper($key, $element, $preserve_keys, $filtered);
			}
		}

		$this->altered = $filtered;

		return $this;
	}

	/**
	 * @param string|int $key
	 * @param mixed      $element
	 * @param bool       $preserve_keys
	 * @param array      $filtered
	 * @return void
	 */
	private function uniqueHelper($key, $element, bool $preserve_keys, array &$filtered)
	{
		if ($preserve_keys) {
			$filtered[ $key ] = $element;

			return;
		}

		$filtered[] = $element;
	}

	/**
	 * @return mixed
	 */
	public function shift()
	{
		return array_shift($this->altered);
	}

	/**
	 * @return mixed
	 */
	public function pop()
	{
		return array_pop($this->altered);
	}

	/**
	 * @return $this
	 */
	public function shuffle()
	{
		$this->altered = shuffle($this->altered);

		return $this;
	}

	/**
	 * @param int   $num
	 * @param mixed $value
	 * @param int   $start
	 * @return $this
	 */
	public function fill(int $num, $value, int $start = 0)
	{
		$filler        = array_fill($start, $num, $value);
		$this->altered = empty($this->altered) ? $filler : array_merge($this->altered, $filler);

		return $this;
	}

	/**
	 * @param mixed $start
	 * @param mixed $end
	 * @param int   $step
	 */
	public function range($start, $end, int $step = 1)
	{
		$range         = range($start, $end, $step);
		$this->altered = empty($this->altered) ? $range : array_merge($this->altered, $range);
	}

	/**
	 * @param string $scope
	 * @param string $delimiter
	 * @return array|mixed|null
	 */
	public function dig(string $scope, string $delimiter = '.')
	{
		$subjects = $this->altered;
		$scopes   = explode($delimiter, $scope);
		$tmp      = [];

		if (isset($subjects[0])) {
			foreach ($subjects as $key => $subject) {
				$tmp[ $key ] = (new Huddle($subject))->dig($scope, $delimiter);
			}
		} else {
			$subject = $subjects;

			foreach ($scopes as $scope) {
				if (!(is_array($subject) && isset($subject[ $scope ]))) {
					$tmp = null;

					break;
				}

				$tmp = $subject = $subject[ $scope ];
			}
		}

		return $tmp;
	}

	/**
	 * @param bool $preserve_keys
	 * @return $this
	 */
	public function reverse(bool $preserve_keys = true)
	{
		$this->altered = array_reverse($this->altered, $preserve_keys);

		return $this;
	}

	/**
	 * @param string $scope
	 * @param bool   $desc
	 * @param string $delimiter
	 * @return $this
	 */
	public function sort(string $scope = null, bool $desc = false, string $delimiter = '.')
	{
		if (is_null($scope)) {
			if (!$desc) {
				sort($this->altered, SORT_REGULAR);
			} else {
				rsort($this->altered, SORT_REGULAR);
			}
		} else {
			$deep = !(strpos($scope, $delimiter) === false);

			usort($this->altered, function ($a, $b) use ($deep, $scope, $delimiter, $desc) {
				if (is_array($a) && is_array($b)) {
					if ($deep) {
						$a = (new Huddle($a))->dig($scope, $delimiter);
						$b = (new Huddle($b))->dig($scope, $delimiter);
					} else {
						$a = isset($a[ $scope ]) ? $a[ $scope ] : null;
						$b = isset($b[ $scope ]) ? $b[ $scope ] : null;
					}
				}

				if ($a == $b) {
					return 0;
				}

				return (!$desc ? ($a < $b) : ($a > $b)) ? -1 : 1;
			});
		}

		return $this;
	}

	/**
	 * @param string $scope
	 * @param string $delimiter
	 * @return $this
	 */
	public function sortDesc(string $scope = null, string $delimiter = '.')
	{
		return $this->sort($scope, true, $delimiter);
	}

	/**
	 * @param bool $desc
	 * @return $this
	 */
	public function sortKeys(bool $desc = false)
	{
		if (!$desc) {
			ksort($this->altered, SORT_REGULAR);
		} else {
			krsort($this->altered, SORT_REGULAR);
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	public function sortKeysDesc()
	{
		return $this->sortKeys(true);
	}
}