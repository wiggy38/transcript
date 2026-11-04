<?php

namespace App\Exceptions;

use RuntimeException;

/** Paiement impossible pour l'instant : formule sans identifiant de prix, ou fournisseur non configuré. */
final class PaiementIndisponible extends RuntimeException {}
