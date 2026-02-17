<?php

namespace App\Policies;

use App\Models\MvClientApplicant;
use App\Models\User;

/**
 * Policy para autorizar acciones sobre MvClientApplicant.
 * 
 * Centraliza la lógica de propiedad que antes estaba duplicada
 * en MveController (~15 veces) y DocumentUploadController (~4 veces).
 *
 * Regla principal: Solo el usuario cuyo email coincide con
 * applicant->user_email puede acceder a sus datos.
 */
class ApplicantPolicy
{
    /**
     * Verificar si el usuario puede ver el recurso.
     */
    public function view(User $user, MvClientApplicant $applicant): bool
    {
        return $this->isOwner($user, $applicant);
    }

    /**
     * Verificar si el usuario puede actualizar el recurso.
     */
    public function update(User $user, MvClientApplicant $applicant): bool
    {
        return $this->isOwner($user, $applicant);
    }

    /**
     * Verificar si el usuario puede eliminar el recurso.
     */
    public function delete(User $user, MvClientApplicant $applicant): bool
    {
        return $this->isOwner($user, $applicant);
    }

    /**
     * Verificar si el usuario puede firmar/enviar manifestaciones del applicant.
     */
    public function sign(User $user, MvClientApplicant $applicant): bool
    {
        return $this->isOwner($user, $applicant);
    }

    /**
     * Verificar si el usuario puede subir documentos al applicant.
     */
    public function uploadDocuments(User $user, MvClientApplicant $applicant): bool
    {
        return $this->isOwner($user, $applicant);
    }

    /**
     * Lógica central de propiedad: el usuario pertenece al mismo grupo admin
     * que el dueño del applicant (Admin + sus Usuarios comparten solicitantes).
     * SuperAdmin nunca tiene acceso.
     */
    private function isOwner(User $user, MvClientApplicant $applicant): bool
    {
        if ($user->role === 'SuperAdmin') {
            return false;
        }

        return $applicant->user_email === $user->getApplicantOwnerEmail();
    }
}
