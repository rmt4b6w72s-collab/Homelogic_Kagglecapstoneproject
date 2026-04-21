import { Navigate, useParams } from 'react-router-dom';

/**
 * Legacy route: /administration/users/:id/edit → users hub opens edit modal for that user.
 */
export default function UserEdit() {
    const { id } = useParams();
    return <Navigate to={`/administration/users?editUserId=${encodeURIComponent(id)}`} replace />;
}
