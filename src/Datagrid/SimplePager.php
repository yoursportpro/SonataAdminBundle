<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\AdminBundle\Datagrid;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @final since sonata-project/admin-bundle 3.52
 *
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 * @author Sjoerd Peters <sjoerd.peters@gmail.com>
 */
class SimplePager extends Pager
{
    /**
     * @var iterable<object>|null
     */
    protected $results;

    /**
     * @var bool
     */
    protected $haveToPaginate;

    /**
     * How many pages to look forward to create links to next pages.
     *
     * @var int
     */
    protected $threshold;

    /**
     * @var int
     */
    protected $thresholdCount;

    /**
     * The threshold parameter can be used to determine how far ahead the pager
     * should fetch results.
     *
     * If set to 1 which is the minimal value the pager will generate a link to the next page
     * If set to 2 the pager will generate links to the next two pages
     * If set to 3 the pager will generate links to the next three pages
     * etc.
     *
     * @param int $maxPerPage Number of records to display per page
     * @param int $threshold
     */
    public function __construct($maxPerPage = 10, $threshold = 1)
    {
        parent::__construct($maxPerPage);
        $this->setThreshold($threshold);
    }

    /**
     * NEXT_MAJOR: remove this method.
     */
    public function getNbResults()
    {
        @trigger_error(sprintf(
            'The method "%s()" is deprecated since sonata-project/admin-bundle 3.86 and will be removed in 4.0. Use countResults() instead.',
            __METHOD__
        ), \E_USER_DEPRECATED);

        return $this->countResults();
    }

    public function countResults(): int
    {
        $n = ($this->getLastPage() - 1) * $this->getMaxPerPage();
        if ($this->getLastPage() === $this->getPage()) {
            return $n + $this->thresholdCount;
        }

        return $n;
    }

    public function getCurrentPageResults(): iterable
    {
        if (null !== $this->results) {
            return $this->results;
        }

        $this->results = $this->getQuery()->execute();
        $this->thresholdCount = \count($this->results);
        if (\count($this->results) > $this->getMaxPerPage()) {
            $this->haveToPaginate = true;

            if ($this->results instanceof ArrayCollection) {
                $this->results = new ArrayCollection($this->results->slice(0, $this->getMaxPerPage()));
            } else {
                $this->results = new ArrayCollection(\array_slice($this->results, 0, $this->getMaxPerPage()));
            }
        } else {
            $this->haveToPaginate = false;
        }

        return $this->results;
    }

    /**
     * NEXT_MAJOR: Remove this method.
     *
     * @deprecated since sonata-project/admin-bundle 3.87. To be removed in 4.0. Use getCurrentPageResults() instead.
     */
    public function getResults($hydrationMode = null)
    {
        @trigger_error(sprintf(
            'The method "%s()" is deprecated since sonata-project/admin-bundle 3.87 and will be removed in 4.0. Use getCurrentPageResults() instead.',
            __METHOD__
        ), \E_USER_DEPRECATED);

        if (null !== $this->results) {
            return $this->results;
        }

        $this->results = $this->getQuery()->execute([], $hydrationMode);
        $this->thresholdCount = \count($this->results);
        if (\count($this->results) > $this->getMaxPerPage()) {
            $this->haveToPaginate = true;

            if ($this->results instanceof ArrayCollection) {
                $this->results = new ArrayCollection($this->results->slice(0, $this->getMaxPerPage()));
            } else {
                $this->results = new ArrayCollection(\array_slice($this->results, 0, $this->getMaxPerPage()));
            }
        } else {
            $this->haveToPaginate = false;
        }

        return $this->results;
    }

    public function haveToPaginate()
    {
        return $this->haveToPaginate || $this->getPage() > 1;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException the query is uninitialized
     */
    public function init()
    {
        if (!$this->getQuery()) {
            throw new \RuntimeException('Uninitialized query');
        }

        // NEXT_MAJOR: Remove this line and uncomment the following one instead.
        $this->resetIterator('sonata_deprecation_mute');
//        $this->haveToPaginate = false;

        if (0 === $this->getPage() || 0 === $this->getMaxPerPage()) {
            $this->setLastPage(0);
            $this->getQuery()->setFirstResult(0);
            $this->getQuery()->setMaxResults(0);
        } else {
            $offset = ($this->getPage() - 1) * $this->getMaxPerPage();
            $this->getQuery()->setFirstResult($offset);

            $maxOffset = $this->getThreshold() > 0
                ? $this->getMaxPerPage() * $this->threshold + 1 : $this->getMaxPerPage() + 1;

            $this->getQuery()->setMaxResults($maxOffset);

            // NEXT_MAJOR: Remove this line and uncomment the following one instead.
            $this->initializeIterator('sonata_deprecation_mute');
//            $this->results = $this->getCurrentPageResults();

            $t = (int) ceil($this->thresholdCount / $this->getMaxPerPage()) + $this->getPage() - 1;
            $this->setLastPage(max(1, $t));
        }
    }

    /**
     * Set how many pages to look forward to create links to next pages.
     *
     * @param int $threshold
     */
    public function setThreshold($threshold)
    {
        $this->threshold = (int) $threshold;
    }

    /**
     * @return int
     */
    public function getThreshold()
    {
        return $this->threshold;
    }

    /**
     * NEXT_MAJOR: Remove this method.
     *
     * @deprecated since sonata-project/admin-bundle 3.84
     */
    protected function resetIterator()
    {
        if ('sonata_deprecation_mute' !== (\func_get_args()[0] ?? null)) {
            @trigger_error(sprintf(
                'The method "%s()" is deprecated since sonata-project/admin-bundle 3.84 and will be removed in 4.0.',
                __METHOD__
            ), \E_USER_DEPRECATED);
        }

        parent::resetIterator('sonata_deprecation_mute');
        $this->haveToPaginate = false;
    }
}
