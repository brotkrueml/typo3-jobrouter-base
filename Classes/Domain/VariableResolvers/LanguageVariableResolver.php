<?php

declare(strict_types=1);

/*
 * This file is part of the "jobrouter_base" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Brotkrueml\JobRouterBase\Domain\VariableResolvers;

use Brotkrueml\JobRouterBase\Enumeration\FieldTypeEnumeration;
use Brotkrueml\JobRouterBase\Event\ResolveFinisherVariableEvent;
use Brotkrueml\JobRouterBase\Exception\VariableResolverException;

/**
 * @internal
 */
final class LanguageVariableResolver
{
    private $validLanguageVariables = [
        'base',
        'direction',
        'flagIdentifier',
        'hreflang',
        'languageId',
        'locale',
        'navigationTitle',
        'title',
        'twoLetterIsoCode',
        'typo3Language',
    ];

    public function __invoke(ResolveFinisherVariableEvent $event): void
    {
        $value = $event->getValue();

        if (!\str_contains($value, '{__language.')) {
            return;
        }

        $this->checkValidFieldTypes($event);

        $language = $event->getRequest()->getAttribute('language', null);
        if ($language === null) {
            return;
        }

        if (!\preg_match_all('/{__language\.(\w+)}/', $value, $matches)) {
            return;
        }

        foreach ($matches[1] as $index => $match) {
            if (!\in_array($match, $this->validLanguageVariables)) {
                continue;
            }

            $methodToCall = 'get' . \ucfirst($match);
            $value = \str_replace($matches[0][$index], $language->$methodToCall(), $value);
        }

        $event->setValue($value);
    }

    private function checkValidFieldTypes(ResolveFinisherVariableEvent $event): void
    {
        if (FieldTypeEnumeration::TEXT === $event->getFieldType()) {
            return;
        }

        throw new VariableResolverException(
            \sprintf(
                'The value "%s" contains a variable which can only be used in Text fields ("%d"), type "%d" used',
                $event->getValue(),
                FieldTypeEnumeration::TEXT,
                $event->getFieldType()
            ),
            1582654966
        );
    }
}
