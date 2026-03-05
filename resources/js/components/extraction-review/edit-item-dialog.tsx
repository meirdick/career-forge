import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { SectionKey } from './types';

interface EditItemDialogProps {
    open: boolean;
    onClose: () => void;
    section: SectionKey;
    item: Record<string, unknown>;
    onSave: (partial: Record<string, unknown>) => void;
}

const FIELD_CONFIG: Record<string, { label: string; fields: { key: string; label: string; type?: string }[] }> = {
    experiences: {
        label: 'Experience',
        fields: [
            { key: 'company', label: 'Company' },
            { key: 'title', label: 'Title' },
            { key: 'location', label: 'Location' },
            { key: 'started_at', label: 'Start Date', type: 'date' },
            { key: 'ended_at', label: 'End Date', type: 'date' },
            { key: 'description', label: 'Description', type: 'textarea' },
        ],
    },
    accomplishments: {
        label: 'Accomplishment',
        fields: [
            { key: 'title', label: 'Title' },
            { key: 'description', label: 'Description', type: 'textarea' },
            { key: 'impact', label: 'Impact' },
        ],
    },
    skills: {
        label: 'Skill',
        fields: [
            { key: 'name', label: 'Name' },
            { key: 'category', label: 'Category' },
        ],
    },
    education: {
        label: 'Education',
        fields: [
            { key: 'institution', label: 'Institution' },
            { key: 'title', label: 'Title' },
            { key: 'field', label: 'Field' },
            { key: 'completed_at', label: 'Completion Date', type: 'date' },
        ],
    },
    projects: {
        label: 'Project',
        fields: [
            { key: 'name', label: 'Name' },
            { key: 'description', label: 'Description', type: 'textarea' },
            { key: 'role', label: 'Role' },
            { key: 'outcome', label: 'Outcome' },
        ],
    },
};

export default function EditItemDialog({ open, onClose, section, item, onSave }: EditItemDialogProps) {
    const [formData, setFormData] = useState<Record<string, unknown>>({ ...item });
    const config = FIELD_CONFIG[section];

    function handleSave() {
        onSave(formData);
        onClose();
    }

    return (
        <Dialog open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Edit {config?.label}</DialogTitle>
                </DialogHeader>
                <div className="space-y-3">
                    {config?.fields.map(({ key, label, type }) => (
                        <div key={key} className="space-y-1">
                            <Label htmlFor={`edit-${key}`}>{label}</Label>
                            {type === 'textarea' ? (
                                <Textarea
                                    id={`edit-${key}`}
                                    rows={4}
                                    value={String(formData[key] ?? '')}
                                    onChange={(e) => setFormData((prev) => ({ ...prev, [key]: e.target.value || undefined }))}
                                />
                            ) : (
                                <Input
                                    id={`edit-${key}`}
                                    type={type ?? 'text'}
                                    value={String(formData[key] ?? '')}
                                    onChange={(e) => setFormData((prev) => ({ ...prev, [key]: e.target.value || undefined }))}
                                />
                            )}
                        </div>
                    ))}
                    <div className="flex justify-end gap-2 pt-2">
                        <Button variant="outline" onClick={onClose}>
                            Cancel
                        </Button>
                        <Button onClick={handleSave}>Save</Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
