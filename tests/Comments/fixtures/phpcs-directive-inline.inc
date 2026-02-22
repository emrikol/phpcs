<?php

// Inline ignore at end of code line — targeted (OK).
$x = 1; // phpcs:ignore Emrikol.PHP.StrictTypes.MissingStrictTypes

// Inline ignore at end of code line — bare (ERROR).
$y = 2; // phpcs:ignore

// Block comment syntax — targeted (OK).
/* phpcs:ignore Emrikol.PHP.StrictTypes.MissingStrictTypes */
$z = 3;

// Block comment syntax — bare (ERROR).
/* phpcs:ignore */
$w = 4;
