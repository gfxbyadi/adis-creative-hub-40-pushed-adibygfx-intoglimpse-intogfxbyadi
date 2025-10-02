import { useLanguageContext } from "@/contexts/language-context"
import { translations } from "@/lib/translations"

export function useLanguage() {
  const { language, setLanguage } = useLanguageContext()

  const t = (key: string): string => {
    const keys = key.split(".")
    let value: any = translations[language]

    for (const k of keys) {
      if (value && typeof value === "object" && k in value) {
        value = value[k]
      } else {
        return key
      }
    }

    return typeof value === "string" ? value : key
  }

  return {
    language,
    setLanguage,
    t,
  }
}
