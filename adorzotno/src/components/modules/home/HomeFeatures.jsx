import { Award, Clock, Shield, Truck } from "lucide-react";
import React from "react";

export default function HomeFeatures() {
  const features = [
    {
      icon: <Truck className="text-primary" size={24} />,
      bg: "bg-primary/10",
      title: "Free Shipping",
      desc: "On orders over ৳50",
    },
    {
      icon: <Shield className="text-green-600" size={24} />,
      bg: "bg-green-100",
      title: "Secure Payment",
      desc: "100% protected",
    },
    {
      icon: <Clock className="text-purple-600" size={24} />,
      bg: "bg-purple-100",
      title: "24/7 Support",
      desc: "Always available",
    },
    {
      icon: <Award className="text-orange-600" size={24} />,
      bg: "bg-orange-100",
      title: "Quality Products",
      desc: "Certified & tested",
    },
  ];

  return (
    <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      {features.map((f, i) => (
        <div
          key={i}
          className="bg-white p-4 rounded-lg shadow-sm border border-blue-100 flex items-center gap-3"
        >
          <div className={`${f.bg} p-3 rounded-full`}>{f.icon}</div>
          <div>
            <h3 className="font-semibold text-primary">{f.title}</h3>
            <p className="text-xs text-gray-400">{f.desc}</p>
          </div>
        </div>
      ))}
    </div>
  );
}
