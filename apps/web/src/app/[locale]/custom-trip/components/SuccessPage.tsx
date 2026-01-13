'use client';

import { useParams } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { Link } from '@/i18n/navigation';
import { Button } from '@go-adventure/ui';
import { CheckCircle, Mail, Clock, Home, PhoneCall, MessageCircle } from 'lucide-react';

interface SuccessPageProps {
  reference: string;
  email: string;
}

export function SuccessPage({ reference, email }: SuccessPageProps) {
  const params = useParams();
  const locale = params?.locale as string;
  const t = useTranslations('customTrip.success');

  return (
    <div className="min-h-[70vh] flex items-center justify-center">
      <div className="max-w-lg w-full mx-auto px-4 py-12 text-center">
        {/* Success Animation */}
        <div className="mb-8">
          <div className="w-20 h-20 mx-auto bg-primary/10 rounded-full flex items-center justify-center animate-pulse">
            <CheckCircle className="w-10 h-10 text-primary" />
          </div>
        </div>

        {/* Title */}
        <h1 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">{t('title')}</h1>
        <p className="text-lg text-gray-600 mb-8">{t('subtitle')}</p>

        {/* Reference Card */}
        <div className="bg-cream rounded-xl p-6 mb-8">
          <p className="text-sm text-gray-600 mb-2">{t('reference_label')}</p>
          <p className="text-2xl font-bold text-primary font-mono">{reference}</p>
        </div>

        {/* Confirmation Email Notice */}
        <div className="bg-white border border-gray-200 rounded-xl p-6 mb-8 text-left">
          <div className="flex items-start gap-4">
            <div className="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center flex-shrink-0">
              <Mail className="w-5 h-5 text-primary" />
            </div>
            <div>
              <h3 className="font-semibold text-gray-900 mb-1">{t('email_sent_title')}</h3>
              <p className="text-sm text-gray-600">{t('email_sent_description', { email })}</p>
            </div>
          </div>
        </div>

        {/* What's Next */}
        <div className="bg-white border border-gray-200 rounded-xl p-6 mb-8 text-left">
          <h3 className="font-semibold text-gray-900 mb-4">{t('whats_next_title')}</h3>
          <ul className="space-y-4">
            <li className="flex items-start gap-3">
              <div className="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-semibold text-gray-600">
                1
              </div>
              <div>
                <p className="font-medium text-gray-900">{t('step1_title')}</p>
                <p className="text-sm text-gray-600">{t('step1_description')}</p>
              </div>
            </li>
            <li className="flex items-start gap-3">
              <div className="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-semibold text-gray-600">
                2
              </div>
              <div>
                <p className="font-medium text-gray-900">{t('step2_title')}</p>
                <p className="text-sm text-gray-600">{t('step2_description')}</p>
              </div>
            </li>
            <li className="flex items-start gap-3">
              <div className="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-semibold text-gray-600">
                3
              </div>
              <div>
                <p className="font-medium text-gray-900">{t('step3_title')}</p>
                <p className="text-sm text-gray-600">{t('step3_description')}</p>
              </div>
            </li>
          </ul>
        </div>

        {/* Response Time */}
        <div className="flex items-center justify-center gap-2 text-gray-600 mb-8">
          <Clock className="w-5 h-5" />
          <span>{t('response_time')}</span>
        </div>

        {/* Action Buttons */}
        <div className="flex flex-col sm:flex-row gap-4 justify-center">
          <Link href="/">
            <Button variant="primary" size="lg" className="w-full sm:w-auto">
              <Home className="w-5 h-5 mr-2" />
              {t('back_home')}
            </Button>
          </Link>
          <a href="https://wa.me/21600000000" target="_blank" rel="noopener noreferrer">
            <Button variant="outline" size="lg" className="w-full sm:w-auto">
              <MessageCircle className="w-5 h-5 mr-2" />
              {t('contact_whatsapp')}
            </Button>
          </a>
        </div>

        {/* Contact Info */}
        <div className="mt-8 pt-8 border-t border-gray-200">
          <p className="text-sm text-gray-500 mb-2">{t('questions')}</p>
          <a
            href="mailto:contact@go-adventure.net"
            className="text-primary hover:text-primary/80 font-medium"
          >
            contact@go-adventure.net
          </a>
        </div>
      </div>
    </div>
  );
}
