import { useState, useRef, useEffect } from "react"
import { Globe, Check } from "lucide-react"
import { Button } from "@/components/ui/button"
import { cn } from "@/lib/utils"
import { useLanguage } from "@/hooks/use-language"

const languages = [
  { code: "en", name: "English", flag: "🇺🇸", country: "United States" },
  { code: "es", name: "Español", flag: "🇪🇸", country: "Spain" },
  { code: "fr", name: "Français", flag: "🇫🇷", country: "France" },
  { code: "de", name: "Deutsch", flag: "🇩🇪", country: "Germany" },
  { code: "it", name: "Italiano", flag: "🇮🇹", country: "Italy" },
  { code: "pt", name: "Português", flag: "🇧🇷", country: "Brazil" },
  { code: "ja", name: "日本語", flag: "🇯🇵", country: "Japan" },
  { code: "zh", name: "中文", flag: "🇨🇳", country: "China" },
  { code: "ru", name: "Русский", flag: "🇷🇺", country: "Russia" },
  { code: "ar", name: "العربية", flag: "🇸🇦", country: "Saudi Arabia" },
]

export function LanguageSelector() {
  const [isOpen, setIsOpen] = useState(false)
  const { language, setLanguage } = useLanguage()
  const dropdownRef = useRef<HTMLDivElement>(null)

  const currentLanguage = languages.find((lang) => lang.code === language) || languages[0]

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
        setIsOpen(false)
      }
    }

    const handleEscape = (event: KeyboardEvent) => {
      if (event.key === "Escape") {
        setIsOpen(false)
      }
    }

    if (isOpen) {
      document.addEventListener("mousedown", handleClickOutside)
      document.addEventListener("keydown", handleEscape)
    }

    return () => {
      document.removeEventListener("mousedown", handleClickOutside)
      document.removeEventListener("keydown", handleEscape)
    }
  }, [isOpen])

  const handleLanguageSelect = (code: string) => {
    setLanguage(code)
    setIsOpen(false)
  }

  return (
    <div className="relative" ref={dropdownRef}>
      <Button
        variant="ghost"
        size="sm"
        onClick={() => setIsOpen(!isOpen)}
        className="h-10 px-3 hover:bg-muted transition-smooth flex items-center gap-2 group"
        aria-label="Select language"
        aria-expanded={isOpen}
        aria-haspopup="true"
      >
        <span className="text-2xl leading-none" role="img" aria-label={currentLanguage.country}>
          {currentLanguage.flag}
        </span>
        <Globe className="h-4 w-4 text-muted-foreground group-hover:text-foreground transition-colors" />
      </Button>

      {isOpen && (
        <div
          className={cn(
            "absolute right-0 mt-2 w-64 bg-card border border-border rounded-xl shadow-lg",
            "animate-in fade-in-0 zoom-in-95 slide-in-from-top-2 duration-200",
            "z-50 overflow-hidden"
          )}
          role="menu"
          aria-orientation="vertical"
        >
          <div className="p-2 space-y-1 max-h-[400px] overflow-y-auto custom-scrollbar">
            {languages.map((lang) => {
              const isSelected = lang.code === language
              return (
                <button
                  key={lang.code}
                  onClick={() => handleLanguageSelect(lang.code)}
                  className={cn(
                    "w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium",
                    "transition-all duration-200 group",
                    isSelected
                      ? "bg-youtube-red/10 text-youtube-red"
                      : "text-foreground hover:bg-muted"
                  )}
                  role="menuitem"
                  aria-current={isSelected ? "true" : "false"}
                >
                  <span
                    className="text-2xl leading-none transition-transform duration-200 group-hover:scale-110"
                    role="img"
                    aria-label={lang.country}
                  >
                    {lang.flag}
                  </span>
                  <span className="flex-1 text-left">{lang.name}</span>
                  {isSelected && (
                    <Check className="h-4 w-4 text-youtube-red animate-in zoom-in-50 duration-200" />
                  )}
                </button>
              )
            })}
          </div>
          <div className="border-t border-border px-3 py-2 bg-muted/50">
            <p className="text-xs text-muted-foreground text-center">
              Select your preferred language
            </p>
          </div>
        </div>
      )}
    </div>
  )
}
