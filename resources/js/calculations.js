export const GoldCalc = {
  /**
   * Calculate Total Weight
   * Formula: Casting Weight + Waste Weight
   */
  totalWeight: (castingWeight, wasteWeight) => {
    return parseFloat((Number(castingWeight) + Number(wasteWeight)).toFixed(3));
  },

  /**
   * Calculate Male Waste
   * Formula: Total Weight ÷ 96 × Ratti
   */
  maleWaste: (totalWeight, ratti, rattiRate) => {
    totalWeight = Number(totalWeight) || 0;
    ratti = Number(ratti) || 0;
    return parseFloat((totalWeight / 96 * ratti).toFixed(3));
  },

  /**
   * Auto-calculate Ratti based on weight tiers
   */
  autoRatti: (totalWeight, tiers) => {
    totalWeight = Number(totalWeight) || 0;
    if (!tiers || !Array.isArray(tiers)) return 0;
    for (const tier of tiers) {
      if (totalWeight <= Number(tier.max_weight)) {
        return parseFloat(Number(tier.ratti).toFixed(2));
      }
    }
    return 0;
  },

  /**
   * Auto-calculate Waste Weight
   * Formula: Casting Weight ÷ 10 × Rate
   */
  autoWaste: (castingWeight, rate) => {
    castingWeight = Number(castingWeight) || 0;
    rate = Number(rate) || 0;
    return parseFloat((castingWeight / 10 * rate).toFixed(3));
  },

  /**
   * Calculate Gold Khalis
   * Formula: Total Weight - Male Waste
   */
  goldKhalis: (totalWeight, maleWaste) => {
    return parseFloat((Number(totalWeight) - Number(maleWaste)).toFixed(3));
  },

  /**
   * Calculate RP Amount
   * Formula: Gold Khalis × RP Rate
   */
  rpAmount: (goldKhalis, rpRate) => {
    return parseFloat((Number(goldKhalis) * Number(rpRate)).toFixed(2));
  },

  /**
   * Calculate Mazdori Amount
   * Formula: Weight × Rate
   */
  mazdoriAmount: (weight, rate) => {
    return parseFloat((Number(weight) * Number(rate)).toFixed(2));
  },

  /**
   * Calculate Effective Gold
   * Formula: Gold Khalis + RP Mazdori Weight + Casting Mazdori Weight
   */
  effectiveGold: (goldKhalis, rpMazdoriWeight, castingMazdoriWeight) => {
    return parseFloat((Number(goldKhalis) + Number(rpMazdoriWeight) + Number(castingMazdoriWeight)).toFixed(3));
  },

  /**
   * Calculate Grand Total
   * Formula: Effective Gold (in grams)
   */
  grandTotal: (effectiveGold, rpRate) => {
    return parseFloat(Number(effectiveGold).toFixed(3));
  },

  /**
   * Calculate Remaining Balance
   * Formula: Previous Balance + Grand Total - Wasooli - Total Received Khalis
   */
  remainingBalance: (previousBalance, grandTotal, wasooli, totalReceivedKhalis = 0) => {
    return parseFloat((Number(previousBalance) + Number(grandTotal) - Number(wasooli) - Number(totalReceivedKhalis)).toFixed(3));
  },

  /**
   * Calculate All Fields at Once
   */
  calculateAll: (input, settings) => {
    const casting = Number(input.casting_weight) || 0;
    
    // Calculate Waste Weight
    let waste = Number(input.waste_weight) || 0;
    if (input.waste_auto) {
      const rate = Number(input.waste_rate || settings?.default_waste_rate) || 0;
      waste = parseFloat((casting / 10 * rate).toFixed(3));
    }

    // Calculate Total Weight (FIXED: + not -)
    const total = parseFloat((casting + waste).toFixed(3));

    // Calculate Ratti
    let ratti = Number(input.ratti) || 0;
    let tierApplied = '';
    if (input.ratti_auto && settings?.ratti_tiers) {
      ratti = 0;
      for (const tier of settings.ratti_tiers) {
        if (total <= Number(tier.max_weight)) {
          ratti = Number(tier.ratti);
          tierApplied = `Total <= ${tier.max_weight}`;
          break;
        }
      }
      if (!tierApplied) {
        ratti = 0.5;
        tierApplied = "Default > last tier";
      }
    }

    // Calculate Male Waste
    const rattiRate = Number(input.ratti_rate) || 0;
    let maleWaste = Number(input.male_waste) || 0;
    if (input.male_waste_auto) {
      maleWaste = parseFloat((total / 96 * ratti).toFixed(3));
    }

    // Calculate Gold Khalis
    const goldKhalis = parseFloat((total - maleWaste).toFixed(3));

    // Calculate Effective Gold
    const rpMazWeight = Number(input.rp_mazdori_weight) || 0;
    const castMazWeight = Number(input.casting_mazdori_weight) || 0;
    const effectiveGold = parseFloat((goldKhalis + rpMazWeight + castMazWeight).toFixed(3));

    // Calculate Grand Total
    const rpRate = Number(input.rp_rate) || 0;
    const grandTotal = effectiveGold;

    // Calculate Remaining Balance
    const prevBalance = Number(input.previous_balance) || 0;
    const wasooli = Number(input.wasooli) || 0;
    const totalReceivedKhalis = Number(input.total_received_khalis) || 0;
    const remaining = parseFloat((prevBalance + grandTotal - wasooli - totalReceivedKhalis).toFixed(3));

    return {
      waste_weight: waste,
      total_weight: total,
      ratti: ratti,
      ratti_tier_applied: tierApplied,
      male_waste: maleWaste,
      gold_khalis: goldKhalis,
      effective_gold: effectiveGold,
      grand_total: grandTotal,
      remaining_balance: remaining,
      rp_amount: parseFloat((goldKhalis * rpRate).toFixed(2)),
      rp_mazdori_amount: parseFloat((rpMazWeight * (Number(input.rp_mazdori_rate) || 0)).toFixed(2)),
      casting_mazdori_amount: parseFloat((castMazWeight * (Number(input.casting_mazdori_rate) || 0)).toFixed(2))
    };
  },

  /**
   * Convert Gross Weight to Khalis (for receive rows)
   * Formula: Gross Weight - (Gross Weight ÷ 96 × Ratti Impurity)
   */
  convertToKhalis: (grossWeight, rattiImpurity) => {
    const g = Number(grossWeight) || 0;
    const r = Number(rattiImpurity) || 0;
    if (g <= 0) return 0;
    return parseFloat((g - ((g / 96) * r)).toFixed(3));
  },

  /**
   * Format Weight for Display (3 decimals)
   */
  formatWeight: (value) => {
    return Number(value || 0).toFixed(3);
  },

  /**
   * Format Amount for Display (2 decimals)
   */
  formatAmount: (value) => {
    return Number(value || 0).toFixed(2);
  },

  /**
   * Format Currency for Display
   */
  formatCurrency: (value) => {
    const number = Number(value || 0);
    return 'Rs. ' + number.toLocaleString('en-PK', { 
      minimumFractionDigits: 2, 
      maximumFractionDigits: 2 
    });
  },
};