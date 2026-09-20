import ProfilePageClient from "@/components/profile/ProfilePageClient";
import { requireAuthenticatedUser } from "@/lib/serverAuth";

export default async function ProfilePage() {
  const user = await requireAuthenticatedUser("/profile");
  return <ProfilePageClient user={user} />;
}
