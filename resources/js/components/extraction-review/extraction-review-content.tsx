import { Check } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import EditItemDialog from './edit-item-dialog';
import { AccomplishmentCard, EducationCard, ExperienceCard, ProjectCard, SkillBadges } from './item-cards';
import type { ExtractionData, SectionKey } from './types';
import { useExtractionReview } from './use-extraction-review';

interface ExtractionReviewContentProps {
    data: ExtractionData;
    onImport: (payload: ExtractionData) => void;
    importing?: boolean;
    compact?: boolean;
}

type EditState = { section: SectionKey; index: number } | null;

export default function ExtractionReviewContent({ data, onImport, importing, compact }: ExtractionReviewContentProps) {
    const review = useExtractionReview(data);
    const [editState, setEditState] = useState<EditState>(null);

    function handleImport() {
        onImport(review.getPayload());
    }

    function getEnhancementKey(section: SectionKey, index: number): `${SectionKey}-${number}` {
        return `${section}-${index}`;
    }

    return (
        <>
            <div className="space-y-6">
                {review.editedData.experiences.length > 0 && (
                    <section className="space-y-3">
                        <h3 className={compact ? 'text-sm font-semibold' : 'text-lg font-semibold'}>
                            Experiences ({review.selected.experiences.size}/{review.editedData.experiences.length})
                        </h3>
                        {review.editedData.experiences.map((exp, i) => (
                            <ExperienceCard
                                key={i}
                                item={exp}
                                selected={review.selected.experiences.has(i)}
                                onToggle={() => review.toggle('experiences', i)}
                                onEdit={() => setEditState({ section: 'experiences', index: i })}
                                onEnhance={() => review.enhanceItem('experiences', i)}
                                enhancing={review.enhancingKey === getEnhancementKey('experiences', i)}
                                pendingEnhancement={review.pendingEnhancements.get(getEnhancementKey('experiences', i)) ?? null}
                                onAcceptEnhancement={() => review.acceptEnhancement('experiences', i)}
                                onRejectEnhancement={() => review.rejectEnhancement('experiences', i)}
                                compact={compact}
                            />
                        ))}
                    </section>
                )}

                {review.editedData.skills.length > 0 && (
                    <>
                        <Separator />
                        <section className="space-y-3">
                            <h3 className={compact ? 'text-sm font-semibold' : 'text-lg font-semibold'}>
                                Skills ({review.selected.skills.size}/{review.editedData.skills.length})
                            </h3>
                            <SkillBadges skills={review.editedData.skills} selected={review.selected.skills} onToggle={review.toggle} />
                        </section>
                    </>
                )}

                {review.editedData.accomplishments.length > 0 && (
                    <>
                        <Separator />
                        <section className="space-y-3">
                            <h3 className={compact ? 'text-sm font-semibold' : 'text-lg font-semibold'}>
                                Accomplishments ({review.selected.accomplishments.size}/{review.editedData.accomplishments.length})
                            </h3>
                            {review.editedData.accomplishments.map((acc, i) => (
                                <AccomplishmentCard
                                    key={i}
                                    item={acc}
                                    selected={review.selected.accomplishments.has(i)}
                                    onToggle={() => review.toggle('accomplishments', i)}
                                    onEdit={() => setEditState({ section: 'accomplishments', index: i })}
                                    onEnhance={() => review.enhanceItem('accomplishments', i)}
                                    enhancing={review.enhancingKey === getEnhancementKey('accomplishments', i)}
                                    pendingEnhancement={review.pendingEnhancements.get(getEnhancementKey('accomplishments', i)) ?? null}
                                    onAcceptEnhancement={() => review.acceptEnhancement('accomplishments', i)}
                                    onRejectEnhancement={() => review.rejectEnhancement('accomplishments', i)}
                                    compact={compact}
                                />
                            ))}
                        </section>
                    </>
                )}

                {review.editedData.education.length > 0 && (
                    <>
                        <Separator />
                        <section className="space-y-3">
                            <h3 className={compact ? 'text-sm font-semibold' : 'text-lg font-semibold'}>
                                Education ({review.selected.education.size}/{review.editedData.education.length})
                            </h3>
                            {review.editedData.education.map((edu, i) => (
                                <EducationCard
                                    key={i}
                                    item={edu}
                                    selected={review.selected.education.has(i)}
                                    onToggle={() => review.toggle('education', i)}
                                    onEdit={() => setEditState({ section: 'education', index: i })}
                                    onEnhance={() => review.enhanceItem('education', i)}
                                    enhancing={review.enhancingKey === getEnhancementKey('education', i)}
                                    pendingEnhancement={review.pendingEnhancements.get(getEnhancementKey('education', i)) ?? null}
                                    onAcceptEnhancement={() => review.acceptEnhancement('education', i)}
                                    onRejectEnhancement={() => review.rejectEnhancement('education', i)}
                                    compact={compact}
                                />
                            ))}
                        </section>
                    </>
                )}

                {review.editedData.projects.length > 0 && (
                    <>
                        <Separator />
                        <section className="space-y-3">
                            <h3 className={compact ? 'text-sm font-semibold' : 'text-lg font-semibold'}>
                                Projects ({review.selected.projects.size}/{review.editedData.projects.length})
                            </h3>
                            {review.editedData.projects.map((proj, i) => (
                                <ProjectCard
                                    key={i}
                                    item={proj}
                                    selected={review.selected.projects.has(i)}
                                    onToggle={() => review.toggle('projects', i)}
                                    onEdit={() => setEditState({ section: 'projects', index: i })}
                                    onEnhance={() => review.enhanceItem('projects', i)}
                                    enhancing={review.enhancingKey === getEnhancementKey('projects', i)}
                                    pendingEnhancement={review.pendingEnhancements.get(getEnhancementKey('projects', i)) ?? null}
                                    onAcceptEnhancement={() => review.acceptEnhancement('projects', i)}
                                    onRejectEnhancement={() => review.rejectEnhancement('projects', i)}
                                    compact={compact}
                                />
                            ))}
                        </section>
                    </>
                )}

                <div className="flex items-center justify-end pt-4">
                    <Button onClick={handleImport} disabled={review.totalSelected === 0 || importing}>
                        <Check className="mr-1 h-4 w-4" /> Import Selected ({review.totalSelected})
                    </Button>
                </div>
            </div>

            {editState && (
                <EditItemDialog
                    open={true}
                    onClose={() => setEditState(null)}
                    section={editState.section}
                    item={review.editedData[editState.section][editState.index] as unknown as Record<string, unknown>}
                    onSave={(partial) => review.updateItem(editState.section, editState.index, partial)}
                />
            )}
        </>
    );
}
