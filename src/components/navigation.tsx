import { useState } from "react"
import { Link, useLocation } from "react-router-dom"
import { Menu, X, Play, Palette, User, Phone, HelpCircle, Briefcase, FileText, Star } from "lucide-react"
import { Button } from "@/components/ui/button"
import { ThemeToggle } from "@/components/theme-toggle"
import { LanguageSelector } from "@/components/language-selector"
import { useLanguage } from "@/hooks/use-language"
import { cn } from "@/lib/utils"

const navigationItems = [
  { key: "home", href: "/", icon: Play },
  { key: "portfolio", href: "/portfolio", icon: Palette },
  { key: "services", href: "/services", icon: Briefcase },
  { key: "about", href: "/about", icon: User },
  { key: "testimonials", href: "/testimonials", icon: Star },
  { key: "blog", href: "/blog", icon: FileText },
  { key: "faq", href: "/faq", icon: HelpCircle },
  { key: "contact", href: "/contact", icon: Phone },
]

export function Navigation() {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false)
  const location = useLocation()
  const { t } = useLanguage()

  return (
    <header className="fixed top-0 left-0 right-0 z-50 bg-background/80 backdrop-blur-md border-b border-border">
      <nav className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">
          {/* Logo */}
          <Link 
            to="/" 
            className="flex items-center space-x-2 font-bold text-xl text-foreground hover:text-youtube-red transition-smooth"
          >
            <div className="w-8 h-8 bg-gradient-youtube rounded-lg flex items-center justify-center">
              <span className="text-white font-bold text-sm">A</span>
            </div>
            <span>Adil GFX</span>
          </Link>

          {/* Desktop Navigation */}
          <div className="hidden md:flex items-center space-x-8">
            {navigationItems.map((item) => {
              const Icon = item.icon
              return (
                <Link
                  key={item.key}
                  to={item.href}
                  className={cn(
                    "flex items-center space-x-1 px-3 py-2 rounded-lg text-sm font-medium transition-smooth",
                    location.pathname === item.href
                      ? "text-youtube-red"
                      : "text-muted-foreground hover:text-foreground hover:bg-muted"
                  )}
                >
                  <Icon className="h-4 w-4" />
                  <span>{t(`nav.${item.key}`)}</span>
                </Link>
              )
            })}
          </div>

          {/* Desktop CTA & Theme Toggle */}
          <div className="hidden md:flex items-center space-x-2">
            <LanguageSelector />
            <ThemeToggle />
            <Link to="/contact">
              <Button className="bg-gradient-youtube hover:shadow-glow transition-all duration-300 font-medium">
                {t("nav.hireMe")}
              </Button>
            </Link>
          </div>

          {/* Mobile menu button */}
          <div className="md:hidden flex items-center space-x-2">
            <LanguageSelector />
            <ThemeToggle />
            <Button
              variant="ghost"
              size="sm"
              onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
              className="h-10 w-10 px-0"
            >
              {mobileMenuOpen ? (
                <X className="h-6 w-6" />
              ) : (
                <Menu className="h-6 w-6" />
              )}
            </Button>
          </div>
        </div>

        {/* Mobile Navigation */}
        {mobileMenuOpen && (
          <div className="md:hidden py-4 space-y-2 border-t border-border mt-4">
            {navigationItems.map((item) => {
              const Icon = item.icon
              return (
                <Link
                  key={item.key}
                  to={item.href}
                  onClick={() => setMobileMenuOpen(false)}
                  className={cn(
                    "flex items-center space-x-3 px-4 py-3 rounded-lg text-sm font-medium transition-smooth",
                    location.pathname === item.href
                      ? "text-youtube-red bg-muted"
                      : "text-muted-foreground hover:text-foreground hover:bg-muted"
                  )}
                >
                  <Icon className="h-5 w-5" />
                  <span>{t(`nav.${item.key}`)}</span>
                </Link>
              )
            })}
            <div className="pt-4 px-4">
              <Link to="/contact">
                <Button className="w-full bg-gradient-youtube hover:shadow-glow transition-all duration-300 font-medium">
                  {t("nav.hireMe")}
                </Button>
              </Link>
            </div>
          </div>
        )}
      </nav>
    </header>
  )
}