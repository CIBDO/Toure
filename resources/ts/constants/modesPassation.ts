export const MODE_PASSATION_OPTIONS = [
  { title: 'Appel d\'offres ouvert', value: 'AO_OUVERT' },
  { title: 'Appel d\'offres restreint', value: 'AO_RESTREINT' },
  { title: 'Consultation', value: 'CONSULTATION' },
  { title: 'Gré à gré', value: 'GRE_A_GRE' },
  { title: 'Entente directe', value: 'ENTENTE_DIRECTE' },
] as const

export const MODE_PASSATION_VALUES = MODE_PASSATION_OPTIONS.map(o => o.value)

export const modePassationLabel = (value: string) =>
  MODE_PASSATION_OPTIONS.find(o => o.value === value)?.title ?? value
