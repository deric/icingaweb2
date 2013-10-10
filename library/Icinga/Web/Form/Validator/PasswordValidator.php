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
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Form\Validator;

use \Zend_Validate_Abstract;

/**
 * Validator that compares the given value with another one
 */
class PasswordValidator extends Zend_Validate_Abstract
{
    /**
     * The messages to write on different error states
     *
     * @var array
     * @see Zend_Validate_Abstract::$_messageTemplates
     */
    // @codingStandardsIgnoreStart
    protected $_messageTemplates = array(
        'NOT_EQUAL' => 'The two passwords do not match'
    );
    // @codingStandardsIgnoreEnd

    /**
     * The name of the counterpart element
     *
     * @var string
     */
    private $counterpartInput;

    /**
     * Set the name of the counterpart element
     *
     * @param   string  $name   The name of the counterpart element
     */
    public function setCounterpart($name)
    {
        $this->counterpartInput = $name;
    }

    /**
     * Check whether the given value is equal to its counterpart element
     *
     * @param   string  $value      The submitted value
     * @param   mixed   $context    The context of the form
     * @return  bool                Whether the submitted value is valid or not
     * @see     Zend_Validate_Abstract::isValid()
     */
    public function isValid($value, $context = null)
    {
        $this->_setValue($value);
        if (!array_key_exists($this->counterpartInput, $context) || $value !== $context[$this->counterpartInput]) {
            $this->_error('NOT_EQUAL');
            return false;
        }
        return true;
    }
}
