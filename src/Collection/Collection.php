<?php
	namespace Adepto\PredicaTree\Collection;

	/**
	 * Collection
	 * A collection of stuff. Completely usable like a normal array.
	 * It is iteratable in foreach and you can count it using count().
	 *
	 * @author bluefirex
	 * @version 2.0
	 * @package as.adepto.lib.common
	 */
	abstract class Collection implements \Iterator, \Countable, \ArrayAccess, \JsonSerializable {
		const NATIVE_TYPECHECKS = [
			'string'		=>	'is_string',
			'int'			=>	'is_int',
			'number'		=>	'is_numeric',
			'float'			=>	'is_float',
			'bool'			=>	'is_boolean',
			'array'			=>	'is_array',
			'object'		=>	'is_object',
			'callable'		=>	'is_callable',
			'double'		=>	'is_double',
			'long'			=>	'is_long',
			'real'			=>	'is_real',
			'resource'		=>	'is_resource'
		];

		public static function fromSpecifiedArray(array $data): Collection {
			$clClass = __NAMESPACE__ . '\\' . $data['type'];

			if (!class_exists($clClass)) {
				throw new \InvalidArgumentException('Collection class ' . $clClass . ' not implemented');
			}

			$items = $data['items'] ?? [];
			return new $clClass($items);
		}

		protected $items;
		protected $type;
		protected $nativeTypeCheck;

		public function __construct(array $items = [], string $type = null) {
			$this->items = $this->readItems($items);
			$this->type = $type;
			$this->nativeTypeCheck = $type && isset(self::NATIVE_TYPECHECKS[$type]) ? self::NATIVE_TYPECHECKS[$type] : null;
		}

		protected abstract function readItems(array $items): array;

		// TODO inject type check automatically
		protected function checkType($arg, bool $throw = false) {
			if ($this->type === null) {
				return true;
			}

			if ($this->nativeTypeCheck) {
				$fn = $this->nativeTypeCheck;
				$ret = $fn($arg);
			} else {
				$ret = $arg instanceof $this->type;
			}

			if (!$ret && $throw) {
				throw new \InvalidArgumentException('Argument is not ' . $this->type);
			}

			return $ret;
		}

		/**
		 * Get the type this collection expects when adding things.
		 * Can be a native type or a class name/path
		 *
		 * @return string
		 */
		public function getType(): string {
			return $this->type;
		}

		/**
		 * Add one or multiple items.
		 * In contrast to set() this can result in duplicates.
		 * @chainable
		 *
		 * @param string $key Key
		 * @param string|array $value If string, single item is added. If array, all of its values are added.
		 *
		 * @return Collection
		 */
		public abstract function add($value, $key = null): Collection;

		/**
		 * Set an item. If exists, override.
		 * @chainable
		 *
		 * @param string|int $key Key
		 * @param mixed $value Value
		 *
		 * @return Collection
		 */
		public abstract function set($key, $value): Collection;

		/**
		 * Remove a key from this collection by association.
		 * @chainable
		 *
		 * @param  string|int $index Key to remove
		 *
		 * @return Collection
		 */
		public abstract function remove($index): Collection;

		/**
		 * Remove a key from this collection by value
		 *
		 * @param mixed $value The value to filter out
		 * @param bool $strict Whether to use strict type comparison or not
		 *
		 * @return Collection
		 */
		public abstract function removeValue($value, bool $strict = false): Collection;

		/**
		 * Remove the last added item.
		 * If item is the only item, remove the association altogether.
		 *
		 * @param  string $key Key
		 * @param bool $strict Whether to use strict type comparison or not
		 *
		 * @return Collection
		 */
		public abstract function removeSingleValue($key, bool $strict = false): Collection;

		public abstract function move($whereFrom, $whereTo): Collection;

		public abstract function moveValue($value, $whereTo, bool $strict = false): Collection;

		public abstract function moveSingleValue($value, $whereTo, bool $strict = false): Collection;

		public abstract function shift($whereFrom, $howMuch): Collection;

		public abstract function shiftValue($value, $howMuch, bool $strict = false): Collection;

		public abstract function shiftSingleValue($value, $howMuch, bool $strict = false): Collection;

		/**
		 * Get an item.
		 *
		 * @param  mixed $where The point to access the collection at. Can be int or string depending on implementation
		 * @param null $default
		 *
		 * @return mixed
		 */
		public abstract function get($where, $default = null);

		/**
		 * Get all items in this collection.
		 *
		 * @return array
		 */
		public abstract function getAll(): array;

		/**
		 * Check if some items in this collection match a callbacks return value.
		 *
		 * @param  callable $fn Callback
		 *
		 * @return bool
		 */
		public function some(callable $fn): bool {
			return count($this->filter($fn)) > 0;
		}

		/**
		 * Check if all items in this collection match a callbacks return value.
		 *
		 * @param  callable $fn Callback
		 *
		 * @return bool
		 */
		public function every(callable $fn): bool {
			return count($this->filter($fn)) == count($this->getAll());
		}

		public function has($value, bool $strictTypeCheck = false): bool {
			return count($this->filter(function($v) use($value, $strictTypeCheck) {
				return in_array($value, (array) $v, $strictTypeCheck);
			})) > 0;
		}

		// Da functional stuff

		/**
		 * Map all items in this collection using a custom callback.
		 * A NEW collection is being returned. No modifications are being made in
		 * the original collection, unless $modify is set to true.
		 *
		 * @param  callable $fn              Callback
		 * @param  bool     $modify = false  Whether to return a new collection or modify in-place
		 *
		 * @return Collection
		 */
		public function map(callable $fn, bool $modify = false): Collection {
			$mapped = array_map($fn, $this->getAll());

			if ($modify) {
				$this->items = $this->readItems($mapped);

				return $this;
			} else {
				return new static($mapped);
			}
		}

		/**
		 * Filter this collection using a custom callback. The callback receives both $value and $key.
		 * Fancy for array_filter($collection->getItems(), $fn, ARRAY_FILTER_USE_BOTH).
		 *
		 * If you want to remove items modifying the original collection, use {@see remove} with a callback.
		 *
		 * @param  callable $fn Callback to decide what to use.
		 * @param bool $modify = false Whether to return a new collection or modify in-place
		 *
		 * @return Collection
		 */
		public function filter(callable $fn, bool $modify = false): Collection {
			$filtered = array_filter($this->getAll(), $fn, ARRAY_FILTER_USE_BOTH);

			if ($modify) {
				$this->items = $this->readItems($filtered);
				return $this;
			} else {
				return new static($filtered);
			}
		}

		/**
		 * Reduce this collection to a simple value. Be it a string, integer, or boolean.
		 *
		 * @param  callable $fn                Callback receives two argument: $carry and $item
		 * @param  mixed    $startValue = null Start Value
		 *
		 * @return mixed
		 */
		public function reduce(callable $fn, $startValue = null) {
			return array_reduce($this->getAll(), $fn, $startValue);
		}

		/**
		 * Return all keys in this collection
		 *
		 * @return array
		 */
		public abstract function keys(): array;

		/**
		 * Return all values in this collection.
		 * Loses key-association, obviously.
		 *
		 * @param  bool  $flatten Whether or not to flatten the result
		 *
		 * @return array
		 */
		public abstract function values(bool $flatten = true): array;

		/**
		 * Get unique values only.
		 * Loses key-association.
		 *
		 * @param  bool  $flatten Whether or not to flatten the result
		 *
		 * @return array
		 */
		public abstract function unique(bool $flatten = true): array;

		// Overloading

		/**
		 * Alias for set($key, $value).
		 *
		 * @param string $key key
		 * @param mixed $value Value
		 *
		 * @return $this
		 */
		public function __set(string $key, $value) {
			return $this->set($key, $value);
		}

		/**
		 * Alias for get($key)
		 *
		 * @param  string $key Key
		 *
		 * @return mixed
		 */
		public function __get(string $key) {
			return $this->get($key);
		}

		/**
		 * Alias for offsetExists($key).
		 *
		 * @param  string  $key Key
		 *
		 * @return boolean
		 */
		public function __isset(string $key): bool {
			return isset($this->getAll()[$key]); // TODO move this down?
		}

		/**
		 * Alias for remove($key).
		 *
		 * @param string $key Key
		 *
		 * @return $this
		 */
		public function __unset(string $key) {
			return $this->remove($key);
		}

		// Iterator
		
		/**
		 * Rewind
		 * Used for iteration
		 */
		public function rewind() {
			return reset($this->items);
		}

		/**
		 * Get the current item.
		 * Used for iteration
		 *
		 * @return mixed
		 */
		public function current() {
			return current($this->items);
		}

		/**
		 * Get the current key.
		 * Used for iteration
		 *
		 * @return string
		 */
		public function key() {
			return key($this->items);
		}

		/**
		 * Get the next item.
		 * Used for iteration
		 */
		public function next() {
			return next($this->items);
		}

		/**
		 * Is the current item valid?
		 * Used for iteration
		 *
		 * @return boolean
		 */
		public function valid(): bool {
			return $this->current() !== false;
		}

		// Countable
		
		/**
		 * Count the number of items in this collection.
		 *
		 * @return int
		 */
		public function count(): int {
			return count($this->getAll());
		}

		// ArrayAccess
		
		public function offsetExists($offset): bool {
			return isset($this->getAll()[$offset]); // TODO move this down?
		}

		public function offsetGet($offset) {
			return $this->get($offset);
		}

		public function offsetSet($offset, $value) {
			$this->set($offset, $value);
		}

		public function offsetUnset($offset) {
			$this->remove($offset);
		}

		// JsonSerializable
		
		public function jsonSerialize() {
			return [
				'type'	=>	str_replace(__NAMESPACE__ . '\\', '', get_class($this)),
				'items'	=>	$this->getAll()
			];
		}

		// static helpers

		protected static function is($a, $b, bool $strict = false): bool {
			return $strict ? $a === $b : $a == $b;
		}

		/**
		 * Negate a function's return value
		 * Helper for internal use
		 *
		 * @param  callable $fn Function to negate
		 *
		 * @return callable      Original function with flipped return value
		 */
		protected static function negateFn(callable $fn): callable {
			return function(...$args) use($fn) {
				return !$fn(...$args);
			};
		}
	}