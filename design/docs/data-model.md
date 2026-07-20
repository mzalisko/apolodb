# Модель даних (для SDD)

## Site
- id, name, domain, status (active|inactive|pending|err), sync (ok|pending|err)
- groups: string[]
- subs: Subdomain[] (кожен закріплений за site)
- token: string
- favorite: bool (обране, зберігається окремо)

## Subdomain
- id, name, domain, status, sync, parentSiteId

## Group
- name (унікальна); членство визначається site.groups
- favorite: bool

## PhoneSlot
- key (id, slug), label?, canonical (E.164), mask
- geoMode (ALL|ONLY|EXCEPT), countries: ISO2[]
- chain: PhoneEntry[]  // [0]=active, далі reserve1, reserve2…
- linkedMessengers: MessengerSlot[] (обчислюється зі зв'язку)
- status/sync

### PhoneEntry
- role (active|reserve1|reserve2…), num (E.164), available: bool

## MessengerSlot
- key, label?, value (акаунт або номер, довільний), badge (2 літери), color
- linkedPhoneKey?: string (може бути прикріплений до будь-якого номера)
- geoMode, countries, status

## PriceSlot
- key, label?, status
- variants: PriceVariant[]

### PriceVariant
- amount (обов'язкове число), currency? (довільний текст, опційно)
- geoMode, countries

## SocialSlot
- key, label?, value (посилання), geoMode, countries, status

## AddressSlot
- key, label?, value (адреса), schedule, linkedPhoneKey? (один номер → кілька адрес)

## User
- id, name, email, role (admin|manager), status (active|suspended), password
- access: map<siteId, level 0|1|2>  // 0 немає, 1 перегляд, 2 редагування

## ChangeLogEntry
- when, who, siteDomain, type (Телефон|Ціна|Месенджер|Гео|Токен), field, from, to
- рівні перегляду: global | site | type
