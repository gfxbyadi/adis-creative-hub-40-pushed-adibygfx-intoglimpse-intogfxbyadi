import { createContext, useContext, useEffect, useState } from "react"

type Language = "en" | "es" | "fr" | "de" | "it" | "pt" | "ja" | "zh" | "ru" | "ar"

type LanguageProviderProps = {
  children: React.ReactNode
  defaultLanguage?: Language
  storageKey?: string
}

type LanguageProviderState = {
  language: Language
  setLanguage: (language: Language) => void
  t: (key: string) => string
}

const initialState: LanguageProviderState = {
  language: "en",
  setLanguage: () => null,
  t: (key: string) => key,
}

const LanguageProviderContext = createContext<LanguageProviderState>(initialState)

export function LanguageProvider({
  children,
  defaultLanguage = "en",
  storageKey = "adil-portfolio-language",
  ...props
}: LanguageProviderProps) {
  const [language, setLanguage] = useState<Language>(() => {
    const stored = localStorage.getItem(storageKey) as Language
    return stored || defaultLanguage
  })

  useEffect(() => {
    const root = window.document.documentElement
    root.setAttribute("lang", language)

    if (language === "ar") {
      root.setAttribute("dir", "rtl")
    } else {
      root.setAttribute("dir", "ltr")
    }
  }, [language])

  const handleSetLanguage = (newLanguage: Language) => {
    localStorage.setItem(storageKey, newLanguage)
    setLanguage(newLanguage)
  }

  const t = (key: string): string => {
    return key
  }

  const value = {
    language,
    setLanguage: handleSetLanguage,
    t,
  }

  return (
    <LanguageProviderContext.Provider {...props} value={value}>
      {children}
    </LanguageProviderContext.Provider>
  )
}

export const useLanguageContext = () => {
  const context = useContext(LanguageProviderContext)

  if (context === undefined) {
    throw new Error("useLanguageContext must be used within a LanguageProvider")
  }

  return context
}
