<?php

declare(strict_types=1);

/**
 * StudentService (Alias / Wrapper für CustomerService)
 *
 * Zur Wahrung der Abwärtskompatibilität und sauberen Namensgebung.
 * Greift auf die Tabelle `bs_customers` zu.
 */
final class StudentService extends CustomerService
{
}
