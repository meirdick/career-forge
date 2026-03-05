import { Head } from '@inertiajs/react';
import FeatureShowcase from '@/components/landing/feature-showcase';
import FinalCta from '@/components/landing/final-cta';
import HeroSection from '@/components/landing/hero-section';
import LandingFooter from '@/components/landing/landing-footer';
import MagicMoment from '@/components/landing/magic-moment';
import PipelineSection from '@/components/landing/pipeline-section';
import TransparencyCallout from '@/components/landing/transparency-callout';
import TrustBar from '@/components/landing/trust-bar';

export default function Welcome({
    canRegister = true,
}: {
    canRegister?: boolean;
}) {
    return (
        <>
            <Head title="CareerForge" />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <HeroSection canRegister={canRegister} />
                <TrustBar />
                <PipelineSection />
                <FeatureShowcase />
                <MagicMoment />
                <TransparencyCallout />
                <FinalCta canRegister={canRegister} />
                <LandingFooter />
            </div>
        </>
    );
}
