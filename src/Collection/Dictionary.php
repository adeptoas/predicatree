<?php

	namespace Adepto\PredicaTree\Collection;

	class Dictionary extends Collection {
		protected function readItems(array $items): array {
			return $items;
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
		public function add($value, $key = null): Collection {
			if (is_array($value)) {
				foreach ($value as $val) {
					$this->add($key, $val);
				}
			} else {
				$this->checkType($value, true);

				if (!isset($this->items[$key])) {
					$this->items[$key] = $value;
				} else {
					if (!is_array($this->items[$key])) {
						$this->items[$key] = [ $this->items[$key] ];
					}

					$this->items[$key][] = $value;
				}
			}

			return $this;
		}

		public function set($key, $value): Collection {
			return $this;
			// TODO: Implement set() method.
		}

		/**
		 * Remove a key from this collection.
		 * If $arg is a function, it will be used to determine, which items to remove.
		 * @chainable
		 *
		 * @param  string|int|callable $arg Key or filter to remove
		 *
		 * @return Collection
		 */
		public function remove($arg): Collection {
			if (is_callable($arg)) {
				$this->items = $this->filter($this->negateFn($arg));
			} else {
				unset($this->items[$arg]);
			}

			return $this;
		}

		public function removeValue($value, bool $strict = false): Collection {
			return $this; // TODO
		}

		public function removeSingleValue($key, bool $strict = false): Collection {
			if (is_array($this->items[$key])) {
				unset($this->items[$key][count($this->items[$key]) - 1]);
			} else {
				$this->remove($key);
			}

			return $this;
		}

		public function move($whereFrom, $whereTo): Collection {
			// TODO
		}

		public function moveValue($value, $whereTo, bool $strict = false): Collection {
			// TODO
		}

		public function moveSingleValue($value, $whereTo, bool $strict = false): Collection {
			// TODO: Implement moveSingleValue() method.
		}

		public function shift($whereFrom, $howMuch): Collection {
			// TODO: Implement shift() method.
		}

		public function shiftValue($value, $howMuch, bool $strict = false): Collection {
			// TODO: Implement shiftValue() method.
		}

		public function shiftSingleValue($value, $howMuch, bool $strict = false): Collection {
			// TODO: Implement shiftSingleValue() method.
		}

		/**
		 * Get an item.
		 *
		 * @param mixed $where
		 * @param null $default
		 *
		 * @return mixed
		 */
		public function get($where, $default = null) {
			return $this->items[$where] ?? null;
		}

		/**
		 * Get all items in this collection.
		 *
		 * @return array
		 */
		public function getAll(): array {
			return $this->items;
		}

		// Da functional stuff

		/**
		 * Return all keys in this collection
		 *
		 * @return array
		 */
		public function keys(): array {
			return array_keys($this->items);
		}

		/**
		 * Return all values in this collection.
		 * Loses key-association, obviously.
		 *
		 * @param  bool  $flatten Whether or not to flatten the result
		 *
		 * @return array
		 */
		public function values(bool $flatten = true): array {
			$values = [];

			foreach ($this->items as $key => $value) {
				if (is_array($value) && $flatten) {
					$values = array_merge($values, $value);
				} else {
					$values[] = $value;
				}
			}

			return $values;
		}

		/**
		 * Get unique values only.
		 * Loses key-association.
		 *
		 * @param  bool  $flatten Whether or not to flatten the result
		 *
		 * @return array
		 */
		public function unique(bool $flatten = true): array {
			return array_values(
				array_unique(
					array_map(
						function($value) {
							return is_array($value) ? array_values(array_unique($value)) : $value;
						}, $this->values($flatten)
					),
					$flatten ? SORT_STRING : SORT_REGULAR
				)
			);
		}
	}