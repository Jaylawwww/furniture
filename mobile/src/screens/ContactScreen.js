import React, { useState } from 'react';
import { Alert, Pressable, StyleSheet, Text, View } from 'react-native';
import { Screen, Input, PrimaryButton } from '../components/Screen';
import { sendContact } from '../api';
import { useAuth } from '../AuthContext';
import { colors, spacing } from '../theme';

const CATEGORIES = [
  { label: 'Customer Support', value: 'support' },
  { label: 'Visit Us', value: 'visit' },
  { label: 'Business', value: 'business' },
];

export default function ContactScreen() {
  const { user } = useAuth();
  const [category, setCategory] = useState('support');
  const [name, setName] = useState(user?.name || '');
  const [email, setEmail] = useState(user?.email || '');
  const [subject, setSubject] = useState('');
  const [message, setMessage] = useState('');
  const [busy, setBusy] = useState(false);

  React.useEffect(() => {
    if (user?.name != null) setName(user.name || '');
    if (user?.email) setEmail(user.email);
  }, [user?.id, user?.name, user?.email]);

  const onSubmit = async () => {
    setBusy(true);
    try {
      const res = await sendContact({ category, name: name.trim(), email: email.trim(), subject: subject.trim(), message: message.trim() });
      Alert.alert('Sent', res.message || 'Message sent.');
      setSubject('');
      setMessage('');
    } catch (e) {
      Alert.alert('Error', e.message || 'Could not send message');
    } finally {
      setBusy(false);
    }
  };

  return (
    <Screen title="Contact us" subtitle="We typically respond within one business day.">
      <Text style={styles.label}>Category</Text>
      <View style={styles.chips}>
        {CATEGORIES.map((item) => (
          <Pressable
            key={item.value}
            style={[styles.chip, category === item.value && styles.chipActive]}
            onPress={() => setCategory(item.value)}
          >
            <Text style={[styles.chipText, category === item.value && styles.chipTextActive]}>
              {item.label}
            </Text>
          </Pressable>
        ))}
      </View>
      <Input label="Name" value={name} onChangeText={setName} />
      <Input label="Email" value={email} onChangeText={setEmail} autoCapitalize="none" keyboardType="email-address" />
      <Input label="Subject" value={subject} onChangeText={setSubject} />
      <Input
        label="Message"
        value={message}
        onChangeText={setMessage}
        multiline
        numberOfLines={5}
        style={styles.message}
      />
      <PrimaryButton label={busy ? 'Sending…' : 'Send message'} onPress={onSubmit} disabled={busy} />
    </Screen>
  );
}

const styles = StyleSheet.create({
  label: { fontSize: 14, fontWeight: '600', color: colors.text, marginBottom: 8 },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginBottom: spacing.md },
  chip: {
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 20,
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.border,
  },
  chipActive: { backgroundColor: colors.primary, borderColor: colors.primary },
  chipText: { color: colors.text, fontSize: 14 },
  chipTextActive: { color: '#fff' },
  message: { minHeight: 120, textAlignVertical: 'top' },
});
