import OrderDetailsPageClient from "@/components/profile/OrderDetailsPageClient";
import { requireAuthenticatedUser } from "@/lib/serverAuth";

export default async function OrderDetailsPage({ params }) {
  const { id } = await params;
  await requireAuthenticatedUser(`/profile/orders/${id}`);
  return <OrderDetailsPageClient orderId={id} />;
}
