<?php

namespace App\Classes;

/**
 * Checks if a given list of cards is legal for tournament play in the Joust and Melee formats.
 * The currently implemented RLs were issued by The Conclave (v2.0), effective July 3rd, 2020.
 * @package App\Classes
 */
class RestrictedListChecker
{
    /**
     * @var array
     */
    const JOUST_RESTRICTED_CARDS = [
        "02065", // Halder (NMG)
        "02091", // Raider from Pyke (CoW)
        "02092", // Iron Mines (CoW)
        "02102", // Ward (TS)
        "04085", // Craster (GoH)
        "06004", // All Men Are Fools (AMAF)
        "06011", // Drowned Disciple (AMAF)
        "06038", // Great Hall (GtR)
        "06098", // Flea Bottom (OR)
        "08080", // The King in the North (FotOG)
        "08082", // I Am No One (TFM)
        "09001", // Mace Tyrell (HoT)
        "09017", // The Hightower (HoT)
        "09051", // Trade Routes (HoT)
        "10048", // Forced March (SoD)
        "10050", // Breaking Ties (SoD)
        "11012", // Nighttime Marauders (TSC)
        "11021", // Wyman Manderly (TMoW)
        "11030", // Bowels of Casterly Rock (TMoW)
        "11033", // Hizdahr zo Loraq (TMoW)
        "11034", // Meereen (TMoW)
        "11051", // Drowned God Fanatic (SoKL)
        "11061", // Meera Reed (MoD)
        "11070", // Clever Feint (MoD)
        "11074", // Meereenese Market (MoD)
        "11082", // Skagos (IDP)
        "11085", // Three-Finger Hobb (IDP)
        "11114", // Gifts for the Widow (DitD)
        "12002", // Euron Crow's Eye (KotI)
        "12029", // Desert Raider (KotI)
        "12045", // Sea of Blood (KotI)
        "12046", // We Take Westeros! (KotI)
        "12047", // Return to the Fields (KotI)
        "13034", // Shadow of the East (CoS)
        "13044", // Unexpected Guile (PoS)
        "13079", // Kingdom of Shadows (BtRK) 
        "13085", // Yoren (TB)
        "13086", // Bound for the Wall (TB)
        "13103", // The Queen's Retinue (LMHR)
        "13118", // Valyrian Steel (LMHR)
        "15017", // Womb of the World (DotE)
        "15046", // Fury of the Khalasar (DotE)
        "15050", // At the Palace of Sorrow (DotE)
        "16013", // Mad King Aerys (TTWDFL)
        "16027", // Aloof and Apart (TTWDFL)
    ];

    /**
     * @var array
     */
    const MELEE_RESTRICTED_CARDS = [
        "01001", // A Clash of Kings (Core)
        "01013", // Heads on Spikes (Core)
        "01043", // Superior Claim (Core)
        "01078", // Great Kraken (Core)
        "01119", // Doran's Game (Core)
        "01146", // Robb Stark (Core)
        "01162", // Khal Drogo (Core)
        "02012", // Rise of the Kraken (TtB)
        "02024", // Lady Sansa's Rose (TRtW)
        "02060", // The Lord of the Crossing (TKP)
        "03003", // Eddard Stark (WotN)
        "04003", // Riverrun (AtSK)
        "04118", // Relentless Assault (TC)
        "05001", // Cersei Lannister (LoCR)
        "06004", // All Men Are Fools (AMAF)
        "06011", // Drowned Disciple (AMAF)
        "06039", // "The Dornishman's Wife" (GtR)
        "06040", // The Annals of Castle Black (GtR)
        "06098", // Flea Bottom (OR)
        "07036", // Plaza of Pride (WotW)
        "08013", // Nagga's Ribs (TAK)
        "08014", // Daario Naharis (TAK)
        "08082", // I Am No One (TFM)
        "08098", // "The Song of the Seven" (TFM)
        "08120", // You Win Or You Die (SAT)
        "09001", // Mace Tyrell (HoT)
        "09028", // Corpse Lake (HoT)
        "11039", // Trading With Qohor (TMoW)
        "11054", // Queensguard (SoKL)
        "13107", // Robert Baratheon (LMHR)
        "15045", // Bribery (DotE)
    ];

    /**
     * @var array
     */
    const JOUST_PODS = [

    ];

    /**
     * @param array $cardCodes
     * @return bool
     */
    public function isLegalForMelee(array $cardCodes)
    {
        return $this->isLegal($cardCodes, self::MELEE_RESTRICTED_CARDS);
    }

    /**
     * @param array $cardCodes
     * @return bool
     */
    public function isLegalForJoust(array $cardCodes)
    {
        return $this->isLegal($cardCodes, self::JOUST_RESTRICTED_CARDS)
            && $this->isPodsLegal($cardCodes, self::JOUST_PODS);
    }

    /**
     * @param array $cardCodes
     * @param array $restrictedList
     * @return bool
     */
    protected function isLegal(array $cardCodes, array $restrictedList)
    {
        $intersection = array_intersect($cardCodes, $restrictedList);
        return 2 > count($intersection);
    }

    /**
     * @param array $cards
     * @param array $pods
     * @return bool
     */
    protected function isPodsLegal(array $cards, array $pods)
    {
        $isLegal = true;
        foreach ($pods as $pod) {
            $restricted = $pod['restricted'];
            if (! in_array($restricted, $cards)) {
                continue;
            }
            if (array_intersect($pod['cards'], $cards)) {
                $isLegal = false;
                break;
            }
        }
        return $isLegal;
    }
}
