import axios from 'axios';
import { useCallback, useEffect, useMemo, useState } from 'react';
import ExperienceEnhanceController from '@/actions/App/Http/Controllers/ExperienceLibrary/ExperienceEnhanceController';
import type { ExtractionData, SectionKey } from './types';

type EnhancementKey = `${SectionKey}-${number}`;

export function useExtractionReview(initialData: ExtractionData) {
    const [editedData, setEditedData] = useState<ExtractionData>(() => structuredClone(initialData));
    const [selected, setSelected] = useState({
        experiences: new Set<number>(),
        accomplishments: new Set<number>(),
        skills: new Set<number>(),
        education: new Set<number>(),
        projects: new Set<number>(),
    });
    const [enhancingKey, setEnhancingKey] = useState<EnhancementKey | null>(null);
    const [pendingEnhancements, setPendingEnhancements] = useState<Map<EnhancementKey, Record<string, unknown>>>(new Map());

    useEffect(() => {
        setEditedData(structuredClone(initialData));
        setSelected({
            experiences: new Set(initialData.experiences.map((_, i) => i)),
            accomplishments: new Set(initialData.accomplishments.map((_, i) => i)),
            skills: new Set(initialData.skills.map((_, i) => i)),
            education: new Set(initialData.education.map((_, i) => i)),
            projects: new Set(initialData.projects.map((_, i) => i)),
        });
        setPendingEnhancements(new Map());
        setEnhancingKey(null);
    }, [initialData]);

    const toggle = useCallback((section: SectionKey, index: number) => {
        setSelected((prev) => {
            const next = new Set(prev[section]);
            if (next.has(index)) {
                next.delete(index);
            } else {
                next.add(index);
            }
            return { ...prev, [section]: next };
        });
    }, []);

    const updateItem = useCallback((section: SectionKey, index: number, partial: Record<string, unknown>) => {
        setEditedData((prev) => {
            const updated = structuredClone(prev);
            const items = updated[section] as Record<string, unknown>[];
            items[index] = { ...items[index], ...partial };
            return updated;
        });
    }, []);

    const enhanceItem = useCallback(async (section: SectionKey, index: number) => {
        if (section === 'skills') return; // Skills don't need enhancement

        const key: EnhancementKey = `${section}-${index}`;
        setEnhancingKey(key);

        // Map section to API section name (skills excluded above)
        const sectionMap: Record<string, string> = {
            experiences: 'experience',
            accomplishments: 'accomplishment',
            education: 'education',
            projects: 'project',
        };

        try {
            const { data } = await axios.post(ExperienceEnhanceController().url, {
                section: sectionMap[section],
                item: editedData[section][index],
            });
            setPendingEnhancements((prev) => new Map(prev).set(key, data));
        } finally {
            setEnhancingKey(null);
        }
    }, [editedData]);

    const acceptEnhancement = useCallback((section: SectionKey, index: number) => {
        const key: EnhancementKey = `${section}-${index}`;
        const enhanced = pendingEnhancements.get(key);
        if (!enhanced) return;

        updateItem(section, index, enhanced);
        setPendingEnhancements((prev) => {
            const next = new Map(prev);
            next.delete(key);
            return next;
        });
    }, [pendingEnhancements, updateItem]);

    const rejectEnhancement = useCallback((section: SectionKey, index: number) => {
        const key: EnhancementKey = `${section}-${index}`;
        setPendingEnhancements((prev) => {
            const next = new Map(prev);
            next.delete(key);
            return next;
        });
    }, []);

    const getPayload = useCallback(() => {
        return {
            experiences: editedData.experiences.filter((_, i) => selected.experiences.has(i)),
            accomplishments: editedData.accomplishments.filter((_, i) => selected.accomplishments.has(i)),
            skills: editedData.skills.filter((_, i) => selected.skills.has(i)),
            education: editedData.education.filter((_, i) => selected.education.has(i)),
            projects: editedData.projects.filter((_, i) => selected.projects.has(i)),
        };
    }, [editedData, selected]);

    const totalSelected = useMemo(
        () =>
            selected.experiences.size +
            selected.accomplishments.size +
            selected.skills.size +
            selected.education.size +
            selected.projects.size,
        [selected],
    );

    return {
        editedData,
        selected,
        enhancingKey,
        pendingEnhancements,
        totalSelected,
        toggle,
        updateItem,
        enhanceItem,
        acceptEnhancement,
        rejectEnhancement,
        getPayload,
    };
}
