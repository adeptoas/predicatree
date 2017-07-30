<?php
	namespace Adepto\PredicaTree\Collection;

	class OrderedSet extends Collection {
		protected function readItems(array $items): array {
			return array_unique(array_values($items));
		}

		public function add($value, $key = null): Collection {
			if (is_array($value)) {
				foreach ($value as $item) {
					$this->add($item, $key);
				}
			} else if (!in_array($value, $this->items)) {
				$this->items[] = $value;
			}

			return $this;
		}

		public function set($key, $value): Collection {
			if (is_int($key)) {
				$this->items[$key] = $value;
			}

			return $this;
		}

		public function remove($index): Collection {
			if (is_int($index)) {
				for ($i = $index + 1; $i < $this->count(); $i++) {
					$this->set($i - 1, $this->get($i));
				}

				unset($this->items[$this->count() - 1]);
			}

			return $this;
		}

		public function removeValue($value, bool $strict = false): Collection {
			for ($i = 0; $i < $this->count(); $i++) {
				if (self::is($this->get($i), $value, $strict)) {
					return $this->remove($i);
				}
			}

			return $this;
		}

		public function removeSingleValue($key, bool $strict = false): Collection {
			return $this->removeValue($key, $strict);
		}

		public function move($whereFrom, $whereTo): Collection {
			if (is_int($whereFrom)) {
				if (is_int($whereTo)) {
					$cache = $this->get($whereFrom);

					// Looks nasty but is actually very concise for direction-independent moving
					$diff = $whereTo - $whereFrom;
					// sign determines where to move (positive: right, negative: left)
					$sign = ($diff > 0) - ($diff < 0); // This is a hack for math abs! gmp_abs freezes the code for some reason…

					// start from $whereFrom and take single steps in the direction of $sign
					// basically continue as long as you have not reached the destination point
					// since we don't know if we approach $whereTo from left or right, we have to use abs
					for ($i = $whereFrom; abs($i - $whereTo) > 0; $i += $sign) {
						// secure the next item to the current position before moving on to its next origin position
						$this->set($i, $this->get($i + $sign));
					}

					$this->set($whereTo, $cache);
				} else if ($whereTo === 'first') {
					return $this->move($whereFrom, 0);
				} else if ($whereTo === 'last') {
					return $this->move($whereFrom, $this->count() - 1);
				}
			}

			return $this;
		}

		public function moveValue($value, $whereTo, bool $strict = false): Collection {
			for ($i = 0; $i < $this->count(); $i++) {
				if (self::is($this->get($i), $value, $strict)) {
					return $this->move($i, $whereTo);
				}
			}

			return $this;
		}

		public function moveSingleValue($value, $whereTo, bool $strict = false): Collection {
			return $this->moveValue($value, $whereTo, $strict);
		}

		public function shift($whereFrom, $howMuch): Collection {
			return $this->move($whereFrom, $whereFrom + $howMuch);
		}

		public function shiftValue($value, $howMuch, bool $strict = false): Collection {
			for ($i = 0; $i < $this->count(); $i++) {
				if (self::is($this->get($i), $value, $strict)) {
					return $this->shift($i, $howMuch);
				}
			}

			return $this;
		}

		public function shiftSingleValue($value, $howMuch, bool $strict = false): Collection {
			return $this->shiftValue($value, $howMuch, $strict);
		}

		public function get($where, $default = null) {
			return $this->items[$where] ?? null;
		}

		public function getAll(): array {
			return array_values($this->items);
		}

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
		 * @param  bool $flatten Whether or not to flatten the result
		 *
		 * @return array
		 */
		public function values(bool $flatten = true): array {
			return array_values($this->items); // TODO potentially flatten
		}

		/**
		 * Get unique values only.
		 * Loses key-association.
		 *
		 * @param  bool $flatten Whether or not to flatten the result
		 *
		 * @return array
		 */
		public function unique(bool $flatten = true): array {
			return $this->values($flatten);
		}
	}