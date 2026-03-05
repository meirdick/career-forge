import AppLogoIcon from '@/components/app-logo-icon';

export default function LandingFooter() {
    return (
        <footer className="border-t px-6 py-10">
            <div className="mx-auto grid max-w-6xl gap-8 text-sm sm:grid-cols-3">
                {/* Brand */}
                <div className="flex items-start gap-2.5">
                    <div className="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-primary">
                        <AppLogoIcon className="size-3.5 fill-current text-primary-foreground" />
                    </div>
                    <div>
                        <div className="font-semibold">CareerForge</div>
                        <div className="text-muted-foreground">AI-Powered Career Management</div>
                    </div>
                </div>

                {/* Links */}
                <div className="flex items-center gap-4 sm:justify-center">
                    <a
                        href="https://github.com"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-muted-foreground hover:text-foreground transition-colors"
                    >
                        GitHub
                    </a>
                </div>

                {/* Tagline + copyright */}
                <div className="text-muted-foreground sm:text-right">
                    <div>Built with transparency in mind</div>
                    <div>&copy; {new Date().getFullYear()} CareerForge</div>
                </div>
            </div>
        </footer>
    );
}
