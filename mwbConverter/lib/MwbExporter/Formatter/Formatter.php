<?php

/*
 * The MIT License
 *
 * Copyright (c) 2010 Johannes Mueller <circus2(at)web.de>
 * Copyright (c) 2012-2013 Toha <tohenk@yahoo.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace MwbExporter\Formatter;

use MwbExporter\Registry\Registry;
use MwbExporter\Model\Base;
use MwbExporter\Model\Catalog;
use MwbExporter\Model\Schemas;
use MwbExporter\Model\Schema;
use MwbExporter\Model\Tables;
use MwbExporter\Model\Table;
use MwbExporter\Model\ForeignKeys;
use MwbExporter\Model\ForeignKey;
use MwbExporter\Model\Indices;
use MwbExporter\Model\Index;
use MwbExporter\Model\Columns;
use MwbExporter\Model\Column;
use MwbExporter\Model\Views;
use MwbExporter\Model\View;

abstract class Formatter implements FormatterInterface
{
    /**
     * @var \MwbExporter\Registry\Registry
     */
    private $registry = null;

    /**
     * @var \MwbExporter\Formatter\DatatypeConverterInterface
     */
    private $datatypeConverter = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->registry = new Registry();
        $this->addConfigurations(array(
            static::CFG_INDENTATION            => 2,
            static::CFG_USE_TABS               => false,
            static::CFG_FILENAME               => '%entity%.%extension%',
            static::CFG_SKIP_PLURAL            => false,
            static::CFG_BACKUP_FILE            => true,
            static::CFG_USE_LOGGED_STORAGE     => false,
            static::CFG_ENHANCE_M2M_DETECTION  => true,
            static::CFG_LOG_TO_CONSOLE         => false,
            static::CFG_LOG_FILE               => '',
        ));
        $this->setDatatypeConverter($this->createDatatypeConverter());
        $this->init();
    }

    /**
     * Initialization.
     */
    protected function init()
    {
    }

    /**
     * Add configurations data.
     *
     * @param string $configurations Configurations data
     * @return \MwbExporter\Formatter\Formatter
     */
    protected function addConfigurations($configurations = array())
    {
        foreach ($configurations as $key => $value)
        {
            $this->registry->config->set($key, $value);
        }

        return $this;
    }

    /**
     * Get all configurations.
     *
     * @return array
     */
    public function getConfigurations()
    {
        return $this->registry->config->getAll();
    }

    /**
     * Setup formatter.
     *
     * @param array $configurations
     * @throws \RuntimeException
     * @return \MwbExporter\Formatter\Formatter
     */
    public function setup($configurations = array())
    {
        foreach ($configurations as $key => $value)
        {
            if (!$this->registry->config->has($key))
            {
                throw new \RuntimeException(sprintf('Unknown setup key "%s".', $key));
            }
            $this->registry->config->set($key, $value);
        }

        return $this;
    }

    /**
     * Create datatype converter instance.
     *
     * @return \MwbExporter\Formatter\DatatypeConverterInterface
     */
    protected function createDatatypeConverter()
    {
    }

    /**
     * Set data type converter.
     *
     * @param \MwbExporter\Formatter\DatatypeConverterInterface $datatypeConverter
     * @return \MwbExporter\Formatter\Formatter
     */
    protected function setDatatypeConverter(DatatypeConverterInterface $datatypeConverter)
    {
        if (null == $datatypeConverter) {
            throw new \RuntimeException('Datatype converted can\'t be null.');
        }
        $this->datatypeConverter = $datatypeConverter;
        $this->datatypeConverter->setup();

        return $this;
    }

    /**
     * Get registry object.
     *
     * @return \MwbExporter\Registry\Registry
     */
    public function getRegistry()
    {
        return $this->registry;
    }

    /**
     * Get data type converter.
     *
     * @return \MwbExporter\Formatter\DatatypeConverterInterface
     */
    public function getDatatypeConverter()
    {
        if (null === $this->datatypeConverter) {
            throw new \RuntimeException('DatatypeConverter has not been set.');
        }

        return $this->datatypeConverter;
    }

    /**
     * (non-PHPdoc)
     * @see \MwbExporter\Formatter\FormatterInterface::createCatalog()
     */
    public function createCatalog(Base $parent, $node)
    {
        return new Catalog($parent, $node);
    }

    /**
     * (non-PHPdoc)
     * @see \MwbExporter\Formatter\FormatterInterface::createSchemas()
     */
    public function createSchemas(Base $parent, $node)
    {
        return new Schemas($parent, $node);
    }

    /**
     * (non-PHPdoc)
     * @see \MwbExporter\Formatter\FormatterInterface::createSchema()
     */
    public function createSchema(Base $parent, $node)
    {
        return new Schema($parent, $node);
    }

    /**
     * (non-PHPdoc)
     * @see \MwbExporter\Formatter\FormatterInterface::createTables()
     */
    public function createTables(Base $parent, $node)
    {
        return new Tables($parent, $node);
    }

    /**
     * (non-PHPdoc)
     * @see \MwbExporter\Formatter\FormatterInterface::createTable()
     */
    public function createTable(Base $parent, $node)
    {
        return new Table($parent, $node);
    }

    /**
     * (non-PHPdoc)
     * @see \MwbExporter\Formatter\FormatterInterface::createForeignKeys()
     */
    public function createForeignKeys(Base $parent, $node)
    {
        return new ForeignKeys($parent, $node);
    }

    /**
     * (non-PHPdoc)
     * @see \MwbExporter\Formatter\FormatterInterface::createForeignKey()
     */
    public function createForeignKey(Base $parent, $node)
    {
        return new ForeignKey($parent, $node);
    }

    /**
     * (non-PHPdoc)
     * @see \MwbExporter\Formatter\FormatterInterface::createIndices()
     */
    public function createIndices(Base $parent, $node)
    {
        return new Indices($parent, $node);
    }

    /**
     * (non-PHPdoc)
     * @see \MwbExporter\Formatter\FormatterInterface::createIndex()
     */
    public function createIndex(Base $parent, $node)
    {
        return new Index($parent, $node);
    }

    /**
     * (non-PHPdoc)
     * @see \MwbExporter\Formatter\FormatterInterface::createColumns()
     */
    public function createColumns(Base $parent, $node)
    {
        return new Columns($parent, $node);
    }

    /**
     * (non-PHPdoc)
     * @see \MwbExporter\Formatter\FormatterInterface::createColumn()
     */
    public function createColumn(Base $parent, $node)
    {
        return new Column($parent, $node);
    }

    /**
     * (non-PHPdoc)
     * @see \MwbExporter\Formatter\FormatterInterface::createViews()
     */
    public function createViews(Base $parent, $node)
    {
        return new Views($parent, $node);
    }

    /**
     * (non-PHPdoc)
     * @see \MwbExporter\Formatter\FormatterInterface::createView()
     */
    public function createView(Base $parent, $node)
    {
        return new View($parent, $node);
    }

    /**
     * (non-PHPdoc)
     * @see \MwbExporter\Formatter\FormatterInterface::getPreferredWriter()
     */
    public function getPreferredWriter()
    {
        return 'default';
    }

    /**
     * (non-PHPdoc)
     * @see \MwbExporter\Formatter\FormatterInterface::getCommentParserIdentifierPrefix()
     */
    public function getCommentParserIdentifierPrefix()
    {
        return 'MwbExporter';
    }
}