<?php
declare(strict_types=1);

function normalizeLocalDateTimeToUtc(string $value): string
{
    if ($value === '') {
        return '';
    }

    $localDateTime = DateTimeImmutable::createFromFormat(
        '!Y-m-d\\TH:i',
        $value,
        new DateTimeZone('Europe/Paris')
    );

    if ($localDateTime === false || $localDateTime->format('Y-m-d\\TH:i') !== $value) {
        return '';
    }

    return $localDateTime
        ->setTimezone(new DateTimeZone('UTC'))
        ->format('Y-m-d H:i:00');
}

function formatFrenchDateTime(?string $value): string
{
    if ($value === null || trim($value) === '') {
        return '';
    }

    try {
        $dateTime = new DateTimeImmutable($value, new DateTimeZone('UTC'));

        return $dateTime
            ->setTimezone(new DateTimeZone('Europe/Paris'))
            ->format('d/m/Y H:i:s');
    } catch (Exception) {
        return $value;
    }
}
