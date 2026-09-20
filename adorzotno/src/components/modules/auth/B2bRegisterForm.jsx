"use client";

import React from "react";
import { useForm, useWatch } from "react-hook-form";

const initialFormData = {
    fullName: "",
    mobileNumber: "",
    businessType: "",
    pharmacyName: "",
    drugLicenseNumber: "",
    drugLicenseFile: null,
    businessName: "",
    tradeLicenseNumber: "",
    tradeLicenseFile: null,
};

export default function B2bRegisterForm() {
    const {
        control,
        register,
        handleSubmit,
        resetField,
        clearErrors,
        formState: { errors, isValid },
    } = useForm({
        defaultValues: initialFormData,
        mode: "onChange",
        shouldUnregister: true,
    });

    const businessType = useWatch({
        control,
        name: "businessType",
        defaultValue: "",
    });
    const isPharmacy = businessType === "Pharmacy";
    const isOthers = businessType === "Others";

    const handleBusinessTypeChange = (event) => {
        const selectedType = event.target.value;

        clearErrors();

        if (selectedType === "Pharmacy") {
            resetField("businessName");
            resetField("tradeLicenseNumber");
            resetField("tradeLicenseFile");
        } else if (selectedType === "Others") {
            resetField("pharmacyName");
            resetField("drugLicenseNumber");
            resetField("drugLicenseFile");
        } else {
            resetField("pharmacyName");
            resetField("drugLicenseNumber");
            resetField("drugLicenseFile");
            resetField("businessName");
            resetField("tradeLicenseNumber");
            resetField("tradeLicenseFile");
        }
    };

    const onSubmit = (data) => {
        console.log({
            ...data,
            drugLicenseFile: data.drugLicenseFile?.[0]?.name ?? null,
            tradeLicenseFile: data.tradeLicenseFile?.[0]?.name ?? null,
        });
    };

    const inputClassName =
        "w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition-all duration-200 placeholder:text-slate-400 focus:border-primary focus:ring-4 focus:ring-primary/10";

    const fileClassName =
        "block w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 px-2 py-2 text-sm text-slate-600 cursor-pointer file:mr-4 file:rounded-lg file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-secondary file:cursor-pointer";

    const businessTypeField = register("businessType", {
        required: "Business Type is required.",
    });

    return (
        <div className="px-6 border rounded-xl py-8 border-slate-200 bg-slate-50/70">
            <h2 className="text-3xl font-semibold mb-4 text-center">
                B2B Registration
            </h2>

            <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
                <div className="grid gap-4 md:grid-cols-2">
                    <div className="md:col-span-2">
                        <label
                            htmlFor="fullName"
                            className="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Full Name
                        </label>
                        <input
                            id="fullName"
                            type="text"
                            placeholder="Enter your full name"
                            className={inputClassName}
                            {...register("fullName", {
                                required: "Full Name is required.",
                            })}
                        />
                        {errors.fullName && (
                            <p className="mt-2 text-sm text-red-500">
                                {errors.fullName.message}
                            </p>
                        )}
                    </div>

                    <div>
                        <label
                            htmlFor="mobileNumber"
                            className="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Mobile Number
                        </label>
                        <input
                            id="mobileNumber"
                            type="tel"
                            placeholder="Enter your mobile number"
                            className={inputClassName}
                            {...register("mobileNumber", {
                                required: "Mobile Number is required.",
                            })}
                        />
                        {errors.mobileNumber && (
                            <p className="mt-2 text-sm text-red-500">
                                {errors.mobileNumber.message}
                            </p>
                        )}
                    </div>

                    <div>
                        <label
                            htmlFor="businessType"
                            className="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Business Type
                        </label>
                        <select
                            id="businessType"
                            className={inputClassName}
                            {...businessTypeField}
                            onChange={(event) => {
                                businessTypeField.onChange(event);
                                handleBusinessTypeChange(event);
                            }}
                        >
                            <option value="">Select business type</option>
                            <option value="Pharmacy">Pharmacy</option>
                            <option value="Others">Others</option>
                        </select>
                        {errors.businessType && (
                            <p className="mt-2 text-sm text-red-500">
                                {errors.businessType.message}
                            </p>
                        )}
                    </div>
                </div>

                {isPharmacy && (
                    <div className="space-y-4">
                        <div>
                            <label
                                htmlFor="pharmacyName"
                                className="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Pharmacy Name
                            </label>
                            <input
                                id="pharmacyName"
                                type="text"
                                placeholder="Enter pharmacy name"
                                className={inputClassName}
                                {...register("pharmacyName", {
                                    required: "Pharmacy Name is required.",
                                })}
                            />
                            {errors.pharmacyName && (
                                <p className="mt-2 text-sm text-red-500">
                                    {errors.pharmacyName.message}
                                </p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="drugLicenseNumber"
                                className="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Drug License Number
                            </label>
                            <input
                                id="drugLicenseNumber"
                                type="text"
                                placeholder="Enter drug license number"
                                className={inputClassName}
                                {...register("drugLicenseNumber", {
                                    required: "Drug License Number is required.",
                                })}
                            />
                            {errors.drugLicenseNumber && (
                                <p className="mt-2 text-sm text-red-500">
                                    {errors.drugLicenseNumber.message}
                                </p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="drugLicenseFile"
                                className="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Upload Drug License
                            </label>
                            <input
                                id="drugLicenseFile"
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png"
                                className={fileClassName}
                                {...register("drugLicenseFile", {
                                    validate: (files) =>
                                        files?.length > 0 || "Drug License file is required.",
                                })}
                            />
                            {errors.drugLicenseFile && (
                                <p className="mt-2 text-sm text-red-500">
                                    {errors.drugLicenseFile.message}
                                </p>
                            )}
                        </div>
                    </div>
                )}

                {isOthers && (
                    <div className="space-y-4">
                        <div>
                            <label
                                htmlFor="businessName"
                                className="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Business Name
                            </label>
                            <input
                                id="businessName"
                                type="text"
                                placeholder="Enter business name"
                                className={inputClassName}
                                {...register("businessName", {
                                    required: "Business Name is required.",
                                })}
                            />
                            {errors.businessName && (
                                <p className="mt-2 text-sm text-red-500">
                                    {errors.businessName.message}
                                </p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="tradeLicenseNumber"
                                className="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Trade License Number
                            </label>
                            <input
                                id="tradeLicenseNumber"
                                type="text"
                                placeholder="Enter trade license number"
                                className={inputClassName}
                                {...register("tradeLicenseNumber", {
                                    required: "Trade License Number is required.",
                                })}
                            />
                            {errors.tradeLicenseNumber && (
                                <p className="mt-2 text-sm text-red-500">
                                    {errors.tradeLicenseNumber.message}
                                </p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="tradeLicenseFile"
                                className="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Upload Trade License
                            </label>
                            <input
                                id="tradeLicenseFile"
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png"
                                className={fileClassName}
                                {...register("tradeLicenseFile", {
                                    validate: (files) =>
                                        files?.length > 0 || "Trade License file is required.",
                                })}
                            />
                            {errors.tradeLicenseFile && (
                                <p className="mt-2 text-sm text-red-500">
                                    {errors.tradeLicenseFile.message}
                                </p>
                            )}
                        </div>
                    </div>
                )}

                <button
                    type="submit"
                    disabled={!isValid}
                    className="w-full rounded-xl bg-primary px-4 py-3.5 text-sm font-semibold text-white transition-all duration-300 hover:bg-secondary disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500"
                >
                    Submit Registration
                </button>
            </form>
        </div>
    );
}
