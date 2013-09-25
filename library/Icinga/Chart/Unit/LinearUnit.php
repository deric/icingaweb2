<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Chart\Unit;

/**
 * Linear tick distribution over the axis
 */
class LinearUnit implements AxisUnit
{

    /**
     * The minimum value to display
     *
     * @var int
     */
    protected $min;

    /**
     * The maximum value to display
     *
     * @var int
     */
    protected $max;

    /**
     * True when the minimum value is static and isn't affected by the dataset
     *
     * @var bool
     */
    protected $staticMin = false;

    /**
     * True when the maximum value is static and isn't affected by the dataset
     *
     * @var bool
     */
    protected $staticMax = false;

    /**
     * The number of ticks to use
     *
     * @var int
     */
    private $nrOfTicks = 10;

    /**
     * The currently displayed tick
     *
     * @var int
     */
    private $currentTick = 0;

    /**
     * The currently displayed value
     * @var int
     */
    private $currentValue = 0;

    /**
     * Create and initialize this AxisUnit
     *
     * @param int $nrOfTicks The number of ticks to use
     */
    public function __construct($nrOfTicks = 10)
    {
        $this->min = PHP_INT_MAX;
        $this->max = ~PHP_INT_MAX;
        $this->nrOfTicks = $nrOfTicks;
    }

    /**
     * Add a dataset and calculate the minimum and maximum value for this AxisUnit
     *
     * @param   array $dataset  The dataset to add
     * @param   int $idx        The idx (0 for x, 1 for y)
     *
     * @return  self            Fluent interface
     */
    public function addValues(array $dataset, $idx = 0)
    {
        $datapoints = array();

        foreach ($dataset['data'] as $points) {
            $datapoints[] = $points[$idx];
        }
        if (empty($datapoints)) {
            return $this;
        }
        sort($datapoints);
        if (!$this->staticMax) {
            $this->max = max($this->max, $datapoints[count($datapoints)-1]);
        }
        if (!$this->staticMin) {
            $this->min = min($this->min, $datapoints[0]);
        }

        $this->currentTick = 0;
        $this->currentValue = $this->min;
        return $this;
    }

    /**
     * Transform the absolute value to an axis relative value
     *
     * @param   int $value  The absolute coordinate from the dataset
     * @return  float|int   The axis relative coordinate (between 0 and 100)
     */
    public function transform($value)
    {
        if ($value < $this->min) {
            return 0;
        } elseif ($value > $this->max) {
            return 100;
        } else {
            return 100 * ($value - $this->min) / ($this->max - $this->min);
        }
    }

    /**
     * Return the position of the current tick
     *
     * @return int
     */
    public function current()
    {
        return $this->currentTick;
    }

    /**
     * Calculate the next tick and tick value
     */
    public function next()
    {
        $this->currentTick += (100 / $this->nrOfTicks);
        $this->currentValue += (($this->max - $this->min) / $this->nrOfTicks);
    }

    /**
     * Return the label for the current tick
     *
     * @return string The label for the current tick
     */
    public function key()
    {
        return (string) $this->currentValue;
    }

    /**
     * True when we're at a valid tick (iterator interface)
     *
     * @return bool
     */
    public function valid()
    {
        return $this->currentTick >= 0 && $this->currentTick <= 100;
    }

    /**
     * Reset the current tick and label value
     */
    public function rewind()
    {
        $this->currentTick = 0;
        $this->currentValue = $this->min;
    }

    /**
     * Set the axis maximum value to a fixed value
     *
     * @param int $max The new maximum value
     */
    public function setMax($max)
    {
        if ($max !== null) {
            $this->max = $max;
            $this->staticMax = true;
        }
    }

    /**
     * Set the axis minimum value to a fixed value
     *
     * @param int $min The new minimum value
     */
    public function setMin($min)
    {
        if ($min !== null) {
            $this->min = $min;
            $this->staticMin = true;
        }
    }

    /**
     * Return the current minimum value of the axis
     *
     * @return int The minimum set for this axis
     */
    public function getMin()
    {
        return $this->min;
    }

    /**
     * Return the current maximum value of the axis
     *
     * @return int The maximum set for this axis
     */
    public function getMax()
    {
        return $this->max;
    }
}
