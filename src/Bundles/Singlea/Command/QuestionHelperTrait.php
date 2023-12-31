<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Command;

use Symfony\Component\Console\Helper\QuestionHelper;

/**
 * @method \Symfony\Component\Console\Helper\HelperInterface getHelper(string $name)
 */
trait QuestionHelperTrait
{
    protected function getQuestionHelper(): QuestionHelper
    {
        $helper = $this->getHelper('question');
        \assert($helper instanceof QuestionHelper);

        return $helper;
    }
}
