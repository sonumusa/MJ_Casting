export const GoldCalc = {
  totalWeight: (castingWeight, wasteWeight) => {
    return parseFloat((Number(castingWeight) + Number(wasteWeight)).toFixed(3));
  },

  maleWaste: (totalWeight, ratti, rattiRate) => {
    totalWeight = Number(totalWeight) || 0;
    ratti = Number(ratti) || 0;
    return parseFloat((totalWeight / 96 * ratti).toFixed(3));
  },

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

  autoWaste: (castingWeight, rate) => {
    castingWeight = Number(castingWeight) || 0;
    rate = Number(rate) || 0;
    return parseFloat((castingWeight / 10 * rate).toFixed(3));
  },

  goldKhalis: (totalWeight, maleWaste) => {
    return parseFloat((Number(totalWeight) - Number(maleWaste)).toFixed(3));
  },

  rpAmount: (goldKhalis, rpRate) => {
    return parseFloat((Number(goldKhalis) * Number(rpRate)).toFixed(2));
  },

  mazdoriAmount: (weight, rate) => {
    return parseFloat((Number(weight) * Number(rate)).toFixed(2));
  },

  effectiveGold: (goldKhalis, rpMazdoriWeight, castingMazdoriWeight) => {
    return parseFloat((Number(goldKhalis) + Number(rpMazdoriWeight) + Number(castingMazdoriWeight)).toFixed(3));
  },

  grandTotal: (effectiveGold, rpRate) => {
    return parseFloat(Number(effectiveGold).toFixed(3));
  },

  remainingBalance: (previousBalance, grandTotal, wasooli, totalReceivedKhalis = 0) => {
    return parseFloat((Number(previousBalance) + Number(grandTotal) - Number(wasooli) - Number(totalReceivedKhalis)).toFixed(3));
  },

  calculateAll: (input, settings) => {
    const casting = Number(input.casting_weight) || 0;
    let waste = Number(input.waste_weight) || 0;
    if (input.waste_auto) {
      const rate = Number(input.waste_rate || settings.default_waste_rate) || 0;
      waste = parseFloat((casting / 10 * rate).toFixed(3));
    }

    const total = parseFloat((casting + waste).toFixed(3));

    let ratti = Number(input.ratti) || 0;
    let tierApplied = '';
    if (input.ratti_auto) {
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

    const rattiRate = Number(input.ratti_rate) || 0;
    let maleWaste = Number(input.male_waste) || 0;
    if (input.male_waste_auto) {
      maleWaste = parseFloat((total / 96 * ratti).toFixed(3));
    }

    const goldKhalis = parseFloat((total - maleWaste).toFixed(3));

    const rpMazWeight = Number(input.rp_mazdori_weight) || 0;
    const castMazWeight = Number(input.casting_mazdori_weight) || 0;
    const effectiveGold = parseFloat((goldKhalis + rpMazWeight + castMazWeight).toFixed(3));

    const rpRate = Number(input.rp_rate) || 0;
    const grandTotal = effectiveGold;

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

  convertToKhalis: (grossWeight, rattiImpurity) => {
    const g = Number(grossWeight) || 0;
    const r = Number(rattiImpurity) || 0;
    if (g <= 0) return 0;
    return parseFloat((g - ((g / 96) * r)).toFixed(3));
  },

  formatWeight: (value) => {
    return Number(value || 0).toFixed(3);
  },

  formatAmount: (value) => {
    return Number(value || 0).toFixed(2);
  },

  formatCurrency: (value) => {
    const number = Number(value || 0);
    return 'Rs. ' + number.toLocaleString('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  },
};
