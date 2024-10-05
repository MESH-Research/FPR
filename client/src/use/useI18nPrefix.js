import { toValue } from "@vueuse/core"
import { inject, provide, ref } from "vue"
import { useI18n } from "vue-i18n"
const PrefixSymbol = Symbol("tPrefix")
export { PrefixSymbol }

export function useI18nPrefix(name) {
  const i18n = useI18n()
  const tPrefix = ref(inject(PrefixSymbol, ""))
  const nameValue = name ? toValue(name).replace(/\[[0-9]+\]/, "") + "." : ""
  //NOTE: This regex strips out the array index from the name of multiple option fields.
  const fullKey = (key) => `${tPrefix.value}.${nameValue}${key}`
  const t = (key) => i18n.t(fullKey(key))
  const te = (key) => i18n.te(fullKey(key))
  const ot = (key) => (te(key) ? t(key) : undefined)
  return {
    prefix: tPrefix,
    fullKey,
    provide: (prefix) => {
      tPrefix.value = prefix
      provide(PrefixSymbol, prefix)
    },
    t,
    te,
    ot,
  }
}
