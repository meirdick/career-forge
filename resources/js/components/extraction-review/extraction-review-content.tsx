import { AlertTriangle, Check } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import EditItemDialog from './edit-item-dialog';
import { AccomplishmentCard, EducationCard, ExperienceCard, LinkBadges, ProjectCard, SkillBadges } from './item-cards';
import type { ExtractionData, MatchAnalysis, SectionKey } from './types';
import { useExtractionReview } from './use-extraction-review';

interface ExtractionReviewContentProps {
    data: ExtractionData;
    onImport: (payload: ExtractionData) => void;
    importing?: boolean;
    compact?: boolean;
    matchAnalysis?: MatchAnalysis;
}

type EditState = { section: SectionKey; index: number } | null;

export default function ExtractionReviewContent({ data, onImport, importing, compact, matchAnalysis }: ExtractionReviewContentProps) {
    const review = useExtractionReview(data);
    const [editState, setEditState] = useState<EditState>(null);

    function handleImport() {
        onImport(review.getPayload());
    }

    function getEnhancementKey(section: SectionKey, index: number): `${SectionKey}-${number}` {
        return `${section}-${index}`;
    }

    // Compute import summary counts from match analysis
    const importSummary = matchAnalysis
        ? Object.values(matchAnalysis.matches).reduce(
              (acc, section) => {
                  Object.values(section).forEach((info) => {
                      if (info.status === 'new') acc.newCount++;
                      else if (info.status === 'will_update') acc.updateCount++;
                      else if (info.status === 'duplicate') acc.duplicateCount++;
                  });
                  return acc;
              },
              { newCount: 0, updateCount: 0, duplicateCount: 0 },
          )
        : null;

    // Build a set of project indices that are part of overlap groups
    const overlappedProjectIndices = new Set<number>();
    const overlapsByExperience = new Map<number, number[]>();
    matchAnalysis?.overlaps?.forEach((group) => {
        overlapsByExperience.set(group.experience_index, group.project_indices);
        group.project_indices.forEach((pi) => overlappedProjectIndices.add(pi));
    });

    return (
        <>
            <div className="space-y-6">
                {review.editedData.experiences.length > 0 && (
                    <section className="space-y-3">
                        <h3 className={compact ? 'text-sm font-semibold' : 'text-lg font-semibold'}>
                            Experiences ({review.selected.experiences.size}/{review.editedData.experiences.length})
                        </h3>
                        {review.editedData.experiences.map((exp, i) => {
                            const overlapProjectIndices = overlapsByExperience.get(i);
                            const card = (
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
                                    matchInfo={matchAnalysis?.matches?.experiences?.[i]}
                                />
                            );

                            if (overlapProjectIndices && overlapProjectIndices.length > 0) {
                                return (
                                    <div key={`overlap-${i}`} className="space-y-2 rounded-lg border-2 border-dashed border-amber-300 p-3 dark:border-amber-700">
                                        <div className="flex items-center gap-2 text-xs text-amber-700 dark:text-amber-400">
                                            <AlertTriangle className="h-3.5 w-3.5" />
                                            <span>These items may describe the same role. Consider importing only one, or deselect the duplicate.</span>
                                        </div>
                                        {card}
                                        {overlapProjectIndices.map((pi) => (
                                            <ProjectCard
                                                key={`overlap-proj-${pi}`}
                                                item={review.editedData.projects[pi]}
                                                selected={review.selected.projects.has(pi)}
                                                onToggle={() => review.toggle('projects', pi)}
                                                onEdit={() => setEditState({ section: 'projects', index: pi })}
                                                onEnhance={() => review.enhanceItem('projects', pi)}
                                                enhancing={review.enhancingKey === getEnhancementKey('projects', pi)}
                                                pendingEnhancement={review.pendingEnhancements.get(getEnhancementKey('projects', pi)) ?? null}
                                                onAcceptEnhancement={() => review.acceptEnhancement('projects', pi)}
                                                onRejectEnhancement={() => review.rejectEnhancement('projects', pi)}
                                                compact={compact}
                                                matchInfo={matchAnalysis?.matches?.projects?.[pi]}
                                            />
                                        ))}
                                    </div>
                                );
                            }

                            return card;
                        })}
                    </section>
                )}

                {review.editedData.skills.length > 0 && (
                    <>
                        <Separator />
                        <section className="space-y-3">
                            <h3 className={compact ? 'text-sm font-semibold' : 'text-lg font-semibold'}>
                                Skills ({review.selected.skills.size}/{review.editedData.skills.length})
                            </h3>
                            <SkillBadges
                                skills={review.editedData.skills}
                                selected={review.selected.skills}
                                onToggle={review.toggle}
                                matchInfoMap={matchAnalysis?.matches?.skills}
                            />
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
                                    matchInfo={matchAnalysis?.matches?.accomplishments?.[i]}
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
                                    matchInfo={matchAnalysis?.matches?.education?.[i]}
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
                            {review.editedData.projects.map((proj, i) => {
                                if (overlappedProjectIndices.has(i)) return null;
                                return (
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
                                        matchInfo={matchAnalysis?.matches?.projects?.[i]}
                                    />
                                );
                            })}
                        </section>
                    </>
                )}

                {review.editedData.urls?.length > 0 && (
                    <>
                        <Separator />
                        <section className="space-y-3">
                            <h3 className={compact ? 'text-sm font-semibold' : 'text-lg font-semibold'}>
                                Links ({review.selected.urls?.size ?? 0}/{review.editedData.urls.length})
                            </h3>
                            <LinkBadges
                                urls={review.editedData.urls}
                                selected={review.selected.urls ?? new Set()}
                                onToggle={review.toggle}
                                matchInfoMap={matchAnalysis?.matches?.urls}
                            />
                        </section>
                    </>
                )}

                <div className="flex items-center justify-between pt-4">
                    {importSummary ? (
                        <div className="flex flex-wrap gap-2">
                            {importSummary.newCount > 0 && (
                                <Badge variant="outline" className="border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-950 dark:text-green-300">
                                    {importSummary.newCount} new
                                </Badge>
                            )}
                            {importSummary.updateCount > 0 && (
                                <Badge variant="outline" className="border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-300">
                                    {importSummary.updateCount} {importSummary.updateCount === 1 ? 'update' : 'updates'}
                                </Badge>
                            )}
                            {importSummary.duplicateCount > 0 && (
                                <Badge variant="outline" className="border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-300">
                                    {importSummary.duplicateCount} {importSummary.duplicateCount === 1 ? 'duplicate' : 'duplicates'}
                                </Badge>
                            )}
                        </div>
                    ) : (
                        <div />
                    )}
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
